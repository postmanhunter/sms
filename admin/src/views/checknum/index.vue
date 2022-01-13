<template>
    <div class="container">
        <div>空号检测次数使用情况:{{num}}               <el-button @click="handle">手动刷新</el-button></div>
        <el-button @click="stop" v-if="check_status=='start'">暂停</el-button>
        <el-button @click="start" v-if="check_status=='stop'">开启</el-button>
        <el-divider></el-divider>
        腾讯云空号检测
        <el-form ref="form" :model="form" label-width="80px">
            <el-form-item label="secretId">
                <el-input v-model="form.secretId"></el-input>
            </el-form-item>
            <el-form-item label="secretKey">
                <el-input v-model="form.secretKey"></el-input>
            </el-form-item>
            <el-form-item>
                <el-button type="primary" @click="onSubmit">提交</el-button>
            </el-form-item>
        </el-form>
    </div>
</template>
<script>
export default {
    components: {},
    data() {
        return {
            check_status:'',
            form:{
                secretId:'',
                secretKey:''
            },
            num:''
        };
    },
    mounted() {
        this.getWeb();
        this.getData();
        this.getNum();
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
        async getNum(){
            let data = await this.http('/api/get_remain_num')
            this.num = data.data.remain;
        },
        async handle(){
            let data = await this.http('/api/handle_deal');
            if(data.code==200000){
                this.$message.success(data.message);
                this.num = data.data.remain
            }else{
                this.$message.error(data.message);
            }
        },
        async getWeb(){
            let data = await this.http('/api/get_web_info');
            this.check_status = data.data.check_status;
        },
         async start(){
            let data = await this.http('/api/start_check_num');
            if(data.code==200000) {
                this.$message.success('空号检测开启成功');
                this.check_status = 'start';
            }else{
                this.$message.error(data.message);
            }
        },
        async stop(){
             let data = await this.http('/api/stop_check_num');
            if(data.code==200000) {
                this.$message.success('空号检测关闭成功');
                this.check_status = 'stop';
            }else{
                this.$message.error('空号检测关闭失败');
            }
        },
        async getData(){
            let data = await this.http('/api/get_num_service');
            if(data.code==200000) {
                this.form = {
                    secretId:data.data.secretId,
                    secretKey:data.data.secretKey,
                }
            }
            
        },
        async onSubmit(){
            let data = await this.http('/api/submit_num_service',this.form);
            if(data.code==200000) {
                this.$message.success('提交成功');
                this.check_status = 'stop';
            }else{
                this.$message.error('提交失败');
            }
        }
    }
};
</script>
<style>
.container {
  margin: 20px 30px;
}
</style>
