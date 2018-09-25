import Vue from 'vue'
import Vuex from 'vuex'

Vue.use(Vuex)

const store = new Vuex.Store({
  state: {
    endpoint: 'http://crm.lets-code.ru/api', //точка входа в api'ху
  },
  actions: {},
  mutations: {},
  getters: {
    endpoint(state) {
      return state.endpoint;
    }
  }
})

export default store
//module.exports = store
