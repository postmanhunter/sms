<template>
    <div class="container">
        <el-form :inline="true" :model="query_form" class="demo-form-inline">
            <el-form-item label="服务商">
                <el-select v-model="query_form.service" placeholder='请选择'>
                    <el-option v-for="item in service" :key="item.id" :value="item.id" :label="item.service_name"></el-option>
                </el-select>
            </el-form-item>
            <el-form-item label="时间">
                <el-date-picker
                    v-model="query_form.time"
                    format="yyyy-MM-dd HH:mm:ss"
                    type="datetimerange"
                    range-separator="至"
                    start-placeholder="开始日期"
                    end-placeholder="结束日期">
                </el-date-picker>
            </el-form-item>
            <el-form-item>
                <el-button type="primary" @click="getList">查询</el-button>
            </el-form-item>
        </el-form>
        <el-divider/>
        <el-button @click="addRecord">添加发送记录</el-button>
        <el-table  :data="tableData" style="width: 100%"  v-loading="loading">
            <el-table-column prop="id" label="Id"></el-table-column>
            <el-table-column prop="service_name" label="服务商"></el-table-column>
            <el-table-column prop="temp_id" label="模板id"></el-table-column>
            <el-table-column prop="time_gap" label="时间间隔（秒）"></el-table-column>
            <el-table-column prop="nums" label="每秒同时发送个数"></el-table-column>
            <el-table-column prop="status_name" label="状态">
                <template slot-scope="scope">
                    <el-tag  v-if="scope.row.status_n=='1'" type="danger">未完成</el-tag>
                    <el-tag  v-if="scope.row.status_n=='2'" type="success">已完成</el-tag>
                </template>
            </el-table-column>
            <el-table-column prop="condition" label="发送情况:已完成/总数"></el-table-column>
            <el-table-column prop="created_at" label="创建时间"></el-table-column>
            <el-table-column label="发送状态">  
                <template slot-scope="scope">
                    <div v-if="scope.row.click_status=='open'">
                        <el-tag class='stop' @click="stop(scope)" v-if="scope.row.send_status=='start'">暂停</el-tag>
                        <el-tag class='stop' @click="start(scope)" v-if="scope.row.send_status=='stop'">开启</el-tag>
                    </div>
                   
                </template>
                
            </el-table-column>
        </el-table>
        <div class="page">
            <el-pagination
            @size-change="handleSizeChange"
            @current-change="handleCurrentChange"
            :current-page="query_form.page"
            :page-sizes="[10, 20, 50]"
            :page-size="query_form.limit"
            layout="total, sizes, prev, pager, next, jumper"
            :total="query_form.count">
            </el-pagination>
        </div>
        <el-dialog
            title="添加发送记录"
            :visible.sync="dialogVisible"
            width="30%">
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
                <el-form-item label="同时发送个数">
                    <el-input v-model="form.nums"></el-input>
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
                        :on-progress="progress"
                        :on-error="error"
                        :limit="1"
                        :multiple="multiple">
                        <el-button size="small" type="primary">点击上传</el-button>
                        <div slot="tip" class="el-upload__tip">只能上传xlsx结尾的，且不能是其他后缀改为xlsx</div>
                        </el-upload>
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="submit" v-loading="loading1">发送</el-button>
                </el-form-item>
            </el-form>
        </el-dialog>
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
                nums:''
            },
            file:'',
            temp:[],
            service:[],
            fileList:[],
            upload_url:'',
            multiple:false,
            num:0,
            tx_callback:'',
            sms_push_status:'',
            dialogVisible:false,
            tableData:[],
            query_form:{
                status:'',
                service:'',
                time:[],
                page:1,
                limit:10,
                count:0
            },
            loading:false,
            loading1:false,
        };
    },
    mounted() {
        this.getServiceList();
        this.getWeb();
        let _this = this;
        let data = setInterval(function(){
            _this.getList1();
        },3000)
        this.getList();
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
        addRecord(){
            this.form = {
                service_id:'',
                temp_id:'',
                time:'',
                file:'',
                nums:''
            }
            this.dialogVisible = true;
            this.delete();
        },
        // async getMessageNum(){
        //     let data = await this.http('/api/get_message_num');
        //     if(data.code==200000){
        //         this.num = data.data.num;
        //     }
        // },
        async getWeb(){
            let data = await this.http('/api/get_web_info');
            this.tx_callback = data.data.tx_callback_url;
            this.upload_url = data.data.upload_url
        },
        async getServiceList(){
            let data = await this.http('/api/get_service_list');
            this.service = data.data; 
            console.log(this.service)
        },
        async getList(){
            this.loading = true;
            let {data} = await this.http('/api/send_list',this.query_form);
            this.tableData = data.data
            this.query_form.count = data.total
            this.loading = false
        },
        async getList1(){
            let {data} = await this.http('/api/send_list',this.query_form);
            this.tableData = data.data
            this.query_form.count = data.total
        },
        async getTempData(val){
            this.temp = [];
            this.form.temp_id = '';
            let data = await this.http('/api/get_temp_list',{service_id:val});
            this.temp = data.data;
        },
        error(){
            this.loading1 = false;
            this.$message.info('当前网络较差，请稍后上传!');
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
            if(!r.test(this.form.nums)){
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
               this.getList();
               this.dialogVisible = false;
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
            let html = document.getElementsByClassName(
            "el-upload-list"
            )
            if(html.innerHTML) {
                html[0].innerHTML = '';
            }
            this.fileList = [];
        },
        success(data){
            this.loading1 = false;
            console.log(data)
            if(data.code!=200000){
                this.$message.error('请上传xlsx文件');
                this.delete();
            }else{
                this.form.file =data.data.url
            }
        },
        progress(){
            this.loading1 = true;
        },
        async start(scope){
            let data = await this.http('/api/start_sms_push',{id:scope.row.id});
            if(data.code==200000) {
                this.$message.success('短信发送开启成功');
                scope.row.send_status = 'start';
            }else{
                this.$message.error('短信发送开启失败');
            }
        },
        async stop(scope){
             let data = await this.http('/api/stop_sms_push',{id:scope.row.id});
            if(data.code==200000) {
                this.$message.success('短信发送关闭成功');
                scope.row.send_status  = 'stop';
            }else{
                this.$message.error('短信发送关闭失败');
            }
        },
        // async clean(){
        //     let data = await this.http('/api/clean_sms_push');
        //     if(data.code==200000) {
        //         this.$message.success('清除成功');
        //         this.num = 0;
        //     }else{
        //         this.$message.error('清除失败');
        //     }
        // },
        handleSizeChange(val){
            this.query_form.limit = val;
            this.getList();
        },
        handleCurrentChange(page){
            this.query_form.page = page;
            this.getList();
        }
    }
};
</script>
<style>
.container {
  margin: 20px 30px;
}
.page{
    margin:20px auto;
}
.stop:hover{
    cursor: pointer;
}
</style>
