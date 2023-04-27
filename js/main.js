(function(){
    var baseContainer = document.getElementById('blf');
    function alpine_data() {
        return {
            message: 'Testing',
            steps: [
                { number: 1, title: 'Steg 1', },
                { number: 2, title: 'Steg 2', },
                { number: 3, title: 'Steg 3', },
            ],
            current_step: 1,
            amount_min: 20000,
            amount_max: 3500000,
            entityTypeArr: [
                { label: 'Ensklid firma', value: 'Sole Proprietorship', },
                { label: 'Aktiebolag', value: 'Limited Company', },
                { label: 'Handelsbolag', value: 'General Partnership', },
                { label: 'Kommanditbolag', value: 'Limited Partnership', },
                { label: 'Annat', value: 'Other', },
            ],
            yrsBusinessArr: [
                { label: 'mindre än 1', value: 'Less than 1', },
                { label: '1', value: '1', },
                { label: '2', value: '2', },
                { label: '3', value: '3', },
                { label: '4', value: '4', },
                { label: '5', value: '5', },
                { label: '6', value: '6', },
                { label: '7', value: '7', },
                { label: '8', value: '8', },
                { label: '9', value: '9', },
                { label: '10', value: '10', },
                { label: 'mer än 10', value: 'More than 10', },
            ],
            industryTypeArr: [
                { label: 'Barnpassning', value: 'Education', },
                { label: 'Bilreparation', value: 'Car services', },
                { label: 'Catering', value: 'Catering', },
                { label: 'Lantbruk, Skog och Fiske', value: 'Agriculture, forestry and fishing', },
                { label: 'Byggnadsentreprenad', value: 'Construction', },
                { label: 'Datorservice', value: 'Computer service', },
                { label: 'Detaljhandel (offline)', value: 'Wholesale and retail', },
                { label: 'Försäljning / Uthyrning av Utrustning', value: 'Equipment rental', },
                { label: 'Hälsa och Fitness', value: 'Health and fitness', },
                { label: 'Hotell / Boende', value: 'Accommodation', },
                { label: 'IT / Mjukvarutjänster', value: 'IT/Software services', },
                { label: 'Juridik', value: 'Lawyer', },
                { label: 'Kemtvätt', value: 'Dry cleaning/Laundry services', },
                { label: 'Läkare', value: 'Doctor', },
                { label: 'Lastbil / Transport', value: 'Transport and storage', },
                { label: 'Livsmedelsbutik', value: 'Grocery store', },
                { label: 'Optiker', value: 'Optician', },
                { label: 'Redovisning / Bokföring', value: 'Accounting', },
                { label: 'Reklam / Marknadsföring', value: 'Commercial/Marketing', },
                { label: 'Restaurang / Bar', value: 'Restaurant/Bar', },
                { label: 'Skönhetssalong / Frisör', value: 'Beauty shop/Hairdresser', },
                { label: 'Städtjänster', value: 'Cleaning services', },
                { label: 'Tandläkare / Tandreglerare', value: 'Dentist', },
                { label: 'Taxi', value: 'Taxi', },
                { label: 'Trädgårdsarkitektur / Trädgårdsanläggning', value: 'Gardening', },
                { label: 'Annat', value: 'Other', },
            ],
            loanPurposeArr: [
                { label: 'Köpa utrustning', value: 'Buying equipment', },
                { label: 'Omformation/Expansion', value: 'Remodeling/Expansion', },
                { label: 'Finansiera skuld', value: 'Refinancing debt', },
                { label: 'Anställa personal', value: 'Hiring employees', },
                { label: 'Rörelsekapital', value: 'Working capital', },
                { label: 'Inköp till lager', value: 'Purchasing inventory', },
                { label: 'Marknadsföring', value: 'Marketing', },
                { label: 'Övrigt', value: 'Other', },
            ],
            user_data: {
                amount: 20000,
                email: '',
                phone: '',
                organization_number: '',
                // step 2 fields
                groupName: '',
                entityType: 'Limited Company',
                yrsBusiness: '5',
                revenue: null,
                industryType: 'Other',
                loanPurpose: 'Working capital',
                businessAddress: '',
                postcode: '',
                city: '',
                // step 3 fields
                firstName: '',
                lastName: '',
                personal_id: '',
                clientCity: '',
                clientAddress: '',
                clientPostcode: '',
                consentDirectMarketing: false,
            },
            validateStep1(step){

                this.current_step = step;
            },
            validateStep2(step){

                this.current_step = step;
            },
            validateStep3(step){
                var self = this;
                if(!self.user_data.consentDirectMarketing){
                    return;
                }
                this.current_step = step;
                jQuery.ajax({
                    method: 'POST',
                    data: self.user_data,
                    url: '/wp-admin/admin-ajax.php?action=blf_ajax_server&handle=submit-form',
                }).done(function(res) {
                    var res = jQuery.parseJSON(res);
                    if(res && res.redirect_url) {
                        window.location = res.redirect_url;
                    } else {
                        console.log(res)
                    }
                }).fail(function(err){
                    console.log(err);
                });
            },
            goToStep(step){
                window.scrollTo({ top: baseContainer.offsetTop - 80, behavior: 'smooth' });
                if(step === 1){ this.current_step = step; }
                if(step === 2){ this.validateStep1(step); }
                if(step === 3){ this.validateStep2(step); }
                if(step === 99){ this.validateStep3(step); }

            },
            init(){
            },
        };
    }
    function alpine_init() { Alpine.data('blf', alpine_data ); }
    document.addEventListener('alpine:init', alpine_init);
})();
