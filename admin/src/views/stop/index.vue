<template>
    <div class="container">
        <div >当前还有<el-tag>{{num}}</el-tag>短信未发送</div>
        <div class="stop">
            <el-button type="primary" @click="stop">暂停</el-button>
        </div>
        
    </div>
</template>
<script>
export default {
    components: {},
    data() {
        return {
            num:''
        };
    },
    mounted() {
        let _this = this;
        let data = setInterval(function(){
            _this.getMessageNum();
        },30000)
    },
    methods: {
        async http(url, params = {}) {
        let data = await this.$http({
            method: "post",
            url: url,
            params: params,
        });
        return data;
        },
        async getMessageNum(){
            let data = await this.http('/api/get_message_num');
            if(data.code==200000){
                this.num = data.data.num;
            }
        },
        async stop(){
            this.$confirm('是否暂停短信发送, 是否继续?', '提示', {
                confirmButtonText: '确定',
                cancelButtonText: '取消',
                type: 'warning',
                center: true
            }).then(async () => {
            let data = await this.http('/api/delete_queue');
            if(data.code==200000){
                this.$message.success(data.message);
                this.getData();
            }else{
                this.$message.error(data.message);
            }
            }).catch(() => {
            
            });
        }
    }
};
</script>
<style>
.container {
  margin: 20px 30px;
}
.stop{
    margin-top:20px;
}
</style>
