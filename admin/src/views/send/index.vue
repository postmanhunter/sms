<template>
    <div class="container">
        <el-form ref="form" :model="form" label-width="110px">
            <el-form-item label="服务商">
                <el-select v-model="form.service_id" @change="getTempData" placeholder="请选择">
                    <el-option v-for="item in service" :key="item.id" :value="item.id" :label="item.service_name"></el-option>
                </el-select>
            </el-form-item>
            <el-form-item label="模板">
                <el-select v-model="form.temp_id" placeholder="请选择">
                    <el-option v-for="item in temp" :key="item.id" :value="item.temp_id" :label="item.temp_id"></el-option>
                </el-select>
            </el-form-item>
            <el-form-item label="时间间隔">
                <el-input v-model="form.time"></el-input>
            </el-form-item>
            <el-form-item label="上传数据文件">
                <el-upload
                    class="upload-demo"
                    :action="upload_url"
                    :on-preview="handlePreview"
                    :on-remove="handleRemove"
                    :before-remove="beforeRemove"
                    :on-exceed="handleExceed"
                    :file-list="fileList"
                    :on-success="success"
                    :limit="1"
                    :multiple="multiple">
                    <el-button size="small" type="primary">点击上传</el-button>
                    </el-upload>
            </el-form-item>
            <el-form-item>
                <el-button type="primary" @click="submit">发送</el-button>
            </el-form-item>
        </el-form>
    </div>
</template>
<script>
export default {
    components: {},
    data() {
        return {
            form:{
                service_id:'',
                temp_id:'',
                time:'',
                file:'',
            },
            file:'',
            temp:[],
            service:[],
            fileList:[],
            upload_url:'',
            multiple:false
        };
    },
    mounted() {
        this.getServiceList();
        this.getWeb();
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
        async getServiceList(){
            let data = await this.http('/api/get_service_list');
            this.service = data.data; 
        },
        async getTempData(val){
            this.temp = [];
            this.form.temp_id = '';
            let data = await this.http('/api/get_temp_list',{service_id:val});
            this.temp = data.data;
        },
        async getWeb(){
            let data = await this.http('/api/get_web_info');
            this.upload_url = data.data.upload_url;
        },
        async submit(){
            if(this.form.service_id==''){
                this.$message.info('请选择服务商');
                return false;
            }
            if(this.form.time==''){
                this.$message.info('请输入时间间隔');
                return false;
            }
            var r = /^\+?[1-9][0-9]*$/;　　//正整数

            if(!r.test(this.form.time)){
                this.$message.info('时间间隔请输入正整数');
                return false;
            }
            if(this.form.temp_id==''){
                this.$message.info('请选择模板');
                return false;
            }
            if(this.form.file==''){
                this.$message.info('请上传信息文件');
                return false;
            }
            let data = await this.http('/api/send',this.form);
            if(data.code!=200000){
                this.$message.error(data.message);
            }else{
               this.$message.success(data.message)
            }

        },
        handleRemove(file, fileList) {
            console.log(file, fileList);
        },
        handlePreview(file) {
            console.log(this.fileList)
            console.log(file);
        },
        handleExceed(files, fileList) {
            this.$message.warning(`当前限制选择 1 个文件，本次选择了 ${files.length} 个文件，共选择了 ${files.length + fileList.length} 个文件`);
        },
        beforeRemove(file, fileList) {
            return this.$confirm(`确定移除 ${ file.name }？`);
        },
         delete() {
            //删除原来的图片
            document.getElementsByClassName(
            "el-upload-list"
            )[0].innerHTML = "";
            this.fileList = [];
        },
        success(data){
            console.log(data)
            if(data.code!=200000){
                this.$message.error('请上传xlsx文件');
                this.delete();
            }else{
                this.form.file =data.data.url
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
