<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <!-- 引入样式 -->
    <link rel="stylesheet" href="/css/element.css">
</head>
<body>
    <div id="app">
        <el-form :model="form" label-width='80px'>
            <el-form-item label='金额'>
                <el-input v-model="form.money"></el-input>
            </el-form-item>
            <el-form-item>
                <el-button @click="submit">提交</el-button>
            </el-form-item>
        </el-form>
    </div>
</body>
<script src="/js/vue.min.js"></script>
<script src="https://unpkg.com/axios/dist/axios.min.js"></script>
<!-- 引入组件库 -->
<script src="/js/element.js"></script>
<script>
var app = new Vue({
    el: '#app',
    data: {
        form:{
            money:'',
        }
    },
    created() {
    },
    methods: {
        http(url,params,method='post'){
            // 发送 POST 请求
            return axios({
                method,
                url,
                params
            });
        },
        async submit(){
            let data = await this.http('/test/api_pay',this.form);
        }
    },
});
</script>
</html>