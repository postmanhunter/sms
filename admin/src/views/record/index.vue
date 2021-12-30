<template>
    <div class="container">
        <el-form :inline="true" :model="form" class="demo-form-inline">
            <el-form-item label="手机号码">
                <el-input v-model="form.mobile" placeholder="手机号码"></el-input>
            </el-form-item>
            <el-form-item label="状态">
                <el-select v-model="form.status" placeholder="状态">
                    <el-option label="请求成功" value="1"></el-option>
                    <el-option label="失败" value="2"></el-option>
                    <el-option label="发送成功" value="3"></el-option>
                </el-select>
            </el-form-item>
            <el-form-item label="时间">
                <el-date-picker
                    v-model="form.time"
                    format="yyyy-MM-dd HH:mm:ss"
                    type="datetimerange"
                    range-separator="至"
                    start-placeholder="开始日期"
                    end-placeholder="结束日期">
                </el-date-picker>
            </el-form-item>
            <el-form-item>
                <el-button type="primary" @click="getData">查询</el-button>
            </el-form-item>
        </el-form>
        <el-table :data="tableData">
            <el-table-column
                prop="id"
                label="ID"
                width="180">
            </el-table-column>
            <el-table-column
                prop="mobile"
                label="电话号码"
                width="180">
            </el-table-column>
            <el-table-column
                label="状态"
                width="180">
                <template slot-scope="scope">
                    <el-tag v-if="scope.row.status==1" type="info">请求成功</el-tag>
                    <el-tag v-if="scope.row.status==2" type="danger">失败</el-tag>
                    <el-tag v-if="scope.row.status==3" type="success">发送成功</el-tag>
                </template>
            </el-table-column>
            <el-table-column
                prop="temp_id"
                label="模板id"
                width="180">
            </el-table-column>
            <el-table-column
                prop="service_name"
                label="服务商"
                width="180">
            </el-table-column>
            <el-table-column
                prop="created_at"
                label="发送时间"
                width="180">
            </el-table-column>
            <el-table-column
                prop="reason"
                label="备注"
                width="180">
            </el-table-column>
            <el-table-column
                prop="request_id"
                label="查询id"
                width="180">
            </el-table-column>
        </el-table>
        <div class="page">
            <el-pagination
            @size-change="handleSizeChange"
            @current-change="handleCurrentChange"
            :current-page="form.page"
            :page-sizes="[20, 50, 100]"
            :page-size="form.limit"
            layout="total, sizes, prev, pager, next, jumper"
            :total="form.count">
            </el-pagination>
        </div>
        
    </div>
</template>
<script>
export default {
    components: {},
    data() {
        return {
            tableData:[],
            form:{
                mobile:'',
                time:[],
                status:'',
                page:1,
                count:0,
                limit:20
            }
        };
    },
    mounted() {
        this.getData();
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
        async getData(){
            let {data} = await this.http('/api/get_record_list',this.form);
            console.log(data)
            this.tableData = data.data;
            this.form.count = data.total;
            
        },
        handleSizeChange(val){
            this.form.limit = val;
            this.getData();
        },
        handleCurrentChange(page){
            this.form.page = page;
            this.getData();
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
</style>
