<template>
    <div class="container">
        <el-button type="warning" @click="addTemp">添加模板</el-button>
        <el-table :data="tableData">
        <el-table-column
            prop="service_name"
            label="所属服务商"
            width="180">
        </el-table-column>
        <el-table-column
            prop="temp_id"
            label="模板id"
            width="180">
        </el-table-column>
        <el-table-column
            prop="params"
            label="模板内容"
            width="340">
        </el-table-column>
        <el-table-column
            prop="temp_content"
            label="模板参数"
            width="180">
        </el-table-column>
        <el-table-column
            prop="created_at"
            label="添加时间"
            width="180">
        </el-table-column>
        <el-table-column
            label="操作"
            width="200">
            <template slot-scope="scope">
            <el-button @click="addTemp(scope.row)" type="text" size="small">编辑</el-button>
            <el-button @click="delTemp(scope.row)" type="text" size="small">删除</el-button>
            </template>
        </el-table-column>
    </el-table>
    <el-dialog
    title="添加模板"
    :visible.sync="dialogVisible"
    width="30%"
    :before-close="handleClose">
      <el-form ref="form" :model="form" label-width="80px">
        <el-form-item label="服务商">
            <el-select v-model="form.service_id">
                <el-option v-for="item in service" :key="item.id" :value="item.id" :label="item.service_name"></el-option>
            </el-select>
        </el-form-item>
        <el-form-item label="模板id">
          <el-input v-model="form.temp_id"></el-input>
        </el-form-item>
        <el-form-item label="模板内容">
          <el-input v-model="form.temp_content"></el-input>
        </el-form-item>
        <el-form-item label='详情'>
            <el-table stripe border :data='form.params'>
                <el-table-column label="参数名">
                    <template slot-scope="scope">
                        <el-input v-model.trim="scope.row.title" type="text" />
                    </template>
                </el-table-column>
                <el-table-column  label="操作">
                    <template slot-scope="scope">
                        <el-button
                            type="text"
                            size="small"
                            @click.native.prevent="delOne(scope.$index)"
                        >删除</el-button>
                    </template>
                </el-table-column>
            </el-table>
            <el-button @click="addOne">增加一项</el-button>
        </el-form-item>
        <el-form-item>

          <el-button type="primary" @click="add_temp">添加</el-button>
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
            dialogVisible:false,
            tableData:[],
            form:{
                id:'',
                temp_id:'',
                temp_content:'',
                service_id:'',
                params:[]
            },
            service:[]
        };
    },
    mounted() {
        this.getData();
        this.getServiceList();
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
            console.log(data)
            this.service = data.data; 
        },
        async getData(){
            let data = await this.http('/api/get_temp_list');
           
            this.tableData = data.data;
            
        },
        async getWeb(){
            let data = await this.http('/api/get_web_info');
            
        },
        addTemp(row=""){
            if(row!=''){
                let data= []
                if(row.params){
                    row.params.forEach((item)=>{
                        data.push(JSON.parse(item))
                    })
                }
                this.form = {
                    temp_id:row.temp_id,
                    temp_content:row.temp_content,
                    id:row.id,
                    service_id:row.service_id,
                    params:data
                }
            }
            this.dialogVisible = true;
        },
        handleClose(){
            this.form = {
                id:'',
                temp_id:'',
                temp_content:''
            };
            this.dialogVisible = false;
        },
        async add_temp(){
            let data = await this.http('/api/add_or_edit_temp',this.form)
            console.log(data)
            if(data.code == 200000){
                this.$message.success(data.message)
                this.dialogVisible = false;
                this.getData();
            }else{
                this.$message.error(data.message)
            }
        },
        async delTemp(row){
            this.$confirm('此操作将永久删除该项, 是否继续?', '提示', {
            confirmButtonText: '确定',
            cancelButtonText: '取消',
            type: 'warning',
            center: true
            }).then(async () => {
            let data = await this.http('/api/del_temp',{id:row.id});
            if(data.code==200000){
                this.$message.success(data.message);
                this.getData();
            }else{
                this.$message.error(data.message);
            }
            }).catch(() => {
            
            });
        },
        addOne(){
            this.form.params.push({title:''})
        },
        delOne(key) {
            this.form.params.splice(key, 1)
        },
    }
};
</script>
<style>
.container {
  margin: 20px 30px;
}
</style>
