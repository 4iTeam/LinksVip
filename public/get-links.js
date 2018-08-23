var clipboard = new ClipboardJS('.btn-copy');
clipboard.on('success', function(e) {
    //linkvip = e.text;
    n = e.text.length;

    alert("Đã copy link download vào clipboard.\nBạn hãy dán link vào trình duyệt hoặc IDM để tải về.\nĐể Resume, bạn hãy dán link vào khung Address trong File Properties của IDM.");

});
clipboard.on('error', function(e) {
    console.log(e);
});
var getLink=new Vue({
    el:'#getLinks',
    data:{
        url:'',
        loading:false,
        link:'',
        result:{},
    },
    computed:{
        domain:function(){
            var a      = document.createElement('a');
            a.href = this.url;
            return a.hostname;
        },
        ready:function(){
            return this.url&&!this.loading;
        }
    },
    methods:{
        getLink:function () {
            this.loading=true;
            this.result={};
            var self=this;
            $.post('v1/links_vip/get',{'link':this.url}).done(function(data){
                self.result=data;
                self.loading=false;
            }).fail(function(response,textStatus){

                var m=textStatus;
                if(response.responseJSON&&response.responseJSON.errors&&response.responseJSON.errors.url){
                    m=response.responseJSON.errors.url.shift();
                }
                if(response.status===419){
                    m='Please reload page!';
                    window.location.reload();
                }
                self.result={status:0,message:m};
                self.loading=false;
            });
        }
    },

});