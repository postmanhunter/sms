<template>
  <div class="container">
    <el-button type="warning" @click="addService">添加服务商</el-button>
    <el-table :data="tableData">
      <el-table-column
        prop="service_name"
        label="服务商名称"
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
          <el-button @click="editParams(scope.row)" type="text" size="small">编辑配置</el-button>
        </template>
      </el-table-column>
    </el-table>
    <el-dialog
    title="添加服务商"
    :visible.sync="dialogVisible"
    width="30%"
    :before-close="handleClose">
      <el-form ref="form" :model="form" label-width="80px">
        <el-form-item label="活动名称">
          <el-input v-model="form.service_name"></el-input>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="add_service">添加</el-button>
        </el-form-item>
      </el-form>
    </el-dialog>
    <el-dialog
    title="配置参数"
    :visible.sync="dialogParams"
    width="30%"
    :before-close="handleCloseParams"> 
    
      <el-button @click="addOne">增加一项</el-button>

      <el-form :model="form" label-width="80px">
        <el-form-item label='详情'>
            <el-table stripe border :data='form.params'>
                <el-table-column label="参数名">
                    <template slot-scope="scope">
                        <el-input v-model.trim="scope.row.title" type="text" />
                    </template>
                </el-table-column>
                <el-table-column label="参数值">
                    <template slot-scope="scope">
                        <el-input v-model.trim="scope.row.value" type="text" />
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
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="add_params">提交</el-button>
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
        dialogParams:false,
        form:{
          id:'',
          service_name:'',
          params:[]
        },
        tableData:[],
    };
  },
  mounted() {
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
     addOne(){
            this.form.params.push({title:'',value:''})
        },
        delOne(key) {
            this.form.params.splice(key, 1)
        },
    editParams(row){
      let data= []
      if(row.params){
        row.params.forEach((item)=>{
            data.push(JSON.parse(item))
        })
      }
      
      this.form.id = row.id;
      this.form.params = data;
      this.dialogParams = true;
    },
    addService(){
      this.dialogVisible = true;
    },
    addParams(){
      this.dialogParams = true;
    },
    async getList(){
      let data = await this.http('/api/get_service_list');
      this.tableData = data.data;
    },
    handleClose(){
      this.form.service_name = '';
      this.dialogVisible = false;
    },
    handleCloseParams(){
      this.form.params = '';
      this.dialogParams = false;
    },
    async add_service(){
      if(this.form.service_name==''){
        this.$message.info('请输入服务商名称');
        return false;
      }
      let data = await this.http('/api/add_service',{service_name:this.form.service_name});
      if(data.code=='200000'){
        this.$message.success('添加成功');
        this.getList();
        this.dialogVisible = false;
      }else{
        this.$message.error('操作失败');
      }
    },
    async add_params(){
      if(this.form.params.length<=0){
        this.$message.info('请配置参数');
        return false;
      }
      let data = await this.http('/api/add_params',{params:this.form.params,id:this.form.id});
      if(data.code=='200000'){
        this.$message.success('添加成功');
        this.getList();
        this.dialogParams = false;
      }else{
        this.$message.error('操作失败');
      }
    }
    
  },
};
</script>
<style>
.container {
  margin: 20px 30px;
}
</style>
