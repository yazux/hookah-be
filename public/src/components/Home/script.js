module.exports = {
  data: function(){
    return {}
  },
  methods: {
    startLog: function(){
      console.log('crm start');
      console.log('endpoint: ' + this.$store.getters.endpoint);
    },
  },
  created: function(){
    this.startLog();
  }
}
