import Vue from 'vue'
import VueResource from 'vue-resource'
import VueRouter from 'vue-router'
import Jsonp from 'jsonp'

import Store from './modules/store'
//import App from './App.vue'


Vue.use(VueResource)
Vue.use(VueRouter)

//Vue.http.headers.common['Authorization'] = localStorage.getItem('id_token');
Vue.http.headers.common['Authorization'] = 'm2l8upkaqtcw0ssg0kgwskk0co4kcss';
Vue.http.headers.common['Accept'] = 'application/json, text/plain, */*';
Vue.http.headers.common['X-Requested-With'] = "XMLHttpRequest";
Vue.http.headers.common['Access-Control-Allow-Origin'] = '*';
Vue.http.options.credentials = true
Vue.http.options.emulateHTTP = true

//routes
import Home from './components/Home/index.vue'

const router = new VueRouter({
  root: '/',
  mode: 'history',
  base: __dirname,
  routeList: {},
  routes: [
    { path: '/', name: 'home', component: Home },
    { path: '/test', name: 'test'},
  ]
});


new Vue({
  el: '#app',
  router: router,
  components: {},
  store: Store,
  data: {},
  computed: {},
  methods: {
    getRouteList() {
      let routes = this.$router.options.routes,
          routeList = {},
          endpoint = this.$store.getters.endpoint;

      routes.forEach(function (route, index, array) {
        routeList[route.name] = {
          'name': route.name,
          'path': endpoint + route.path,
          'component': route.component
        }
      });
      this.$router.options.routeList = routeList;
    }
  },
  created:  function(){
    this.getRouteList();

    this.$http.get( this.$router.options.routeList.test.path, {}).then(
      response => {
        if( response.status == 200){
          console.log( response.body );
        }
      },
      response => {
        console.log( response );
      }
    );

    //Jsonp(this.$router.options.routeList.test.path, (error, response) => {});

  },
  //render: h => h(App),
});
