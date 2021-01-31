<template>
    <div class="container pt-5">

        <div v-if="success" class="alert alert-success">
            {{ success }}
        </div>
        <div v-if="errors" class="alert alert-danger">
            <strong v-if="errors.message">{{errors.message}}</strong>
            <ul v-if="errors.errors.csv">
                <li v-for="error in errors.errors.csv">{{ error }}</li>
            </ul>
        </div>

        <div class="alert alert-info" v-if="uploading">
            Uploading file <span v-if="save_to_database">and saving to database</span>, be patient.
        </div>

        <form @submit.prevent="upload" enctype="multipart/form-data" id="upload_form">
            <div class="form-input">
                <input type="file" name="csv" @change="onFileChange">
            </div>
            <div class="form-input">
                <input type="checkbox" name="save_to_database" v-model="save_to_database">
                <label>Save to database</label>
            </div>
            <div class="form-input">
                <button type="submit" class="submit-btn" :disabled="csv == '' || uploading">Submit</button>
            </div>
        </form>

        <div>
            <p>
                <strong>Avg price:</strong>
                {{ average_price }}
            </p>
            <p>
                <strong>Total houses sold:</strong>
                {{ total_houses_sold }}
            </p>
            <p>
                <strong>No of crimes in 2011:</strong>
                {{ number_of_crimes }}
            </p>
            <p>
                <strong>Avg price per year in London area</strong>
            </p>
            <p v-for="(item, index) in avg_price_per_year_london">
                {{ index }}: {{ item }}
            </p>
        </div>
    </div>
</template>

<script>
    export default {

        data(){
            return {
                errors: '',
                success: '',
                csv: '',
                save_to_database: false,
                average_price: '',
                total_houses_sold: '',
                number_of_crimes: '',
                avg_price_per_year_london: '',
                uploading: false,
            }
        },

        methods: {
            onFileChange(e){
                let csv = e.target.files;
                this.errors = '';
                this.success = '';

                if (!csv.length){
                    this.errors = 'Csv required.'
                    return;
                }

                this.csv = csv[0]
            },

            upload(){
                this.uploading = true;
                let data = new FormData();

                data.append('save_to_database', this.save_to_database)
                data.append('csv', this.csv);

                axios.post('/api/upload_csv', data, {
                    headers: { 'content-type': 'multipart/form-data' }
                })
                    .then(response => {
                        this.average_price = response.data.average_price;
                        this.avg_price_per_year_london = response.data.avg_price_per_year_in_london;
                        this.total_houses_sold = response.data.houses_sold;
                        this.number_of_crimes = response.data.number_of_crimes;
                        this.success = response.data.message;
                    })
                    .catch(error => {
                        this.errors = error.response.data;
                    })
                    .finally(() => {
                        this.uploading = false;
                    })
            },
        },

    }
</script>

<style>
    .container{
        font-family: 'Helvetica'
    }
    .form-input{
        margin-bottom: 5px;
    }
    .submit-btn{
        margin-top: 10px;
        background-color: lightgrey;
        border: none;
        padding: 10px;
    }
</style>
