<?php

namespace App\Http\Controllers;

use App\CsvMonthly;
use App\Http\Requests\CsvUploadRequest;
use App\ImportCsvMonthly;
use Carbon\Carbon;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class UploadCsvFileController extends Controller
{
    const NUMBER_OF_CRIMES_YEAR = 2011;
    const CITY_OF_LONDON_AREA = 'city of london';
    const LONDON_AREA = 'london';

    public function uploadFile(CsvUploadRequest $request)
    {
        try{
            $file = $request->file('csv');

            $request->file('csv')->storeAs('', $file->getClientOriginalName(), 'public');
            $csv = Storage::disk('public')->path($file->getClientOriginalName());

            $results = [];
            if (($handle = fopen($csv, "r")) !== false) {
                while (($data = fgetcsv($handle, 1000, ",")) !== false) {
                    $results[] = $data;
                }
                fclose($handle);
            }

            if ($request->get('save_to_database') == 'true'){

                $model_data = ImportCsvMonthly::all();

                if ($model_data->count() != 0){ // Truncate all data if table not empty.
                    ImportCsvMonthly::truncate();
                }

                foreach ($results as $key => $value){

                    if ($key == 0){
                        continue;
                    }

                    DB::table('london_housing_monthly')->insert([ // This is faster than Model::create
                        'date' => $value[$this->column('date', $results)],
                        'area' => (string) $value[$this->column('area', $results)],
                        'average_price' => (integer) $value[$this->column('average_price', $results)],
                        'code' => (string) $value[$this->column('code', $results)],
                        'houses_sold' => (integer) $value[$this->column('houses_sold', $results)],
                        'no_of_crimes' =>  (double)$value[$this->column('no_of_crimes', $results)],
                        'borough_flag' => (bool) $value[$this->column('borough_flag', $results)],
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now()
                    ]);

                }
            }

            return response()->json([
                'average_price' => $this->calculateAveragePrice($results),
                'houses_sold' => $this->countSoldHouses($results),
                'number_of_crimes' => $this->numberOfCrimes($results),
                'avg_price_per_year_in_london' => $this->averagePricePerYearInLondonArea($results),
                'message' => 'Csv file uploaded.',
            ], Response::HTTP_OK);

        }catch (\Exception $exception){
            throw new \Exception($exception->getMessage());
        }
    }


    public function column($name, $data){

        if (!isset($name)){
            return;
        }

        $column = null;
        foreach ($data as $k => $v) {
            if ($k == 0){
                $column = $v;
                break;
            }
        }

        return array_search($name, $column, false);
    }

    public function calculateAveragePrice($data)
    {

        $houses_sold = $this->column('houses_sold', $data);
        $average_price = $this->column('average_price', $data);

        return intval(Collection::make($data)->map(function ($item, $key) use ($houses_sold){

                if ($key == 0){ // Skip csv headings.
                    return;
                }

                if (!empty($item[$houses_sold]) && $item[$houses_sold] > 0){ // Skip empty and zero values.
                    return $item;
                }

            })->avg($average_price));

    }

    public function countSoldHouses($data)
    {

        $houses_sold = $this->column('houses_sold', $data);

        return intval(Collection::make($data)->map(function($item, $key) use ($houses_sold) {

            if ($key == 0){ // Skip csv headings.
                return;
            }

            if (!empty($item[$houses_sold]) && $item[$houses_sold] > 0){ // Skip empty and zero values.
                return $item;
            }

        })->sum($houses_sold));

    }

    public function numberOfCrimes($data)
    {

        $date = $this->column('date', $data);
        $no_of_crimes = $this->column('no_of_crimes', $data);

        return intval(Collection::make($data)->map(function($item, $key) use ($date, $no_of_crimes){

            if ($key == 0){
                return;
            }

            if ( !empty($item[$no_of_crimes]) && $item[$no_of_crimes] > 0){

                $year = Carbon::createFromDate($item[$date])->year;

                if ($year == self::NUMBER_OF_CRIMES_YEAR){
                    return $item;
                }
            }

        })->sum($no_of_crimes));

    }

    public function averagePricePerYearInLondonArea($data)
    {

        $area = $this->column('area', $data);
        $date = $this->column('date', $data);
        $average_price = $this->column('average_price', $data);


        return Collection::make($data)->map(function ($item, $key) use ($area, $date, $average_price) {

            if ($key == 0) {
                return;
            }

            if ($item[$area] == self::CITY_OF_LONDON_AREA || $item[$area] == self::LONDON_AREA) {
                $item[$date] = Carbon::createFromDate($item[$date])->year;

                return $item;
            }

        })->groupBy($date)->map(function ($item) use ($average_price) {
            return intval($item->avg($average_price));
        });
    }
}
