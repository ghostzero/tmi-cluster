<template>
  <div>
    <div class="bg-gradient text-white mb-3">
      <div class="py-5 container">
        <div class="d-flex pb-4 justify-content-between">
          <div>
            <h1 class="font-weight-bold">TMI Cluster</h1>
            <h3 class="mb-0 text-white-50">Service Status</h3>
          </div>
          <div class="lead mb-0 align-self-center">
            <span class="d-none d-sm-inline">
              <a href="https://tmiphp.com" target="_blank" class="text-white-50">Powered by TMI.php</a>
            </span>
          </div>
        </div>
      </div>
    </div>

    <div class="container">
      <div class="alert alert-primary bg-white mb-4 py-4" style="margin-top: -55px;">
        <div class="h5 mb-0 font-weight-bold" v-if="statistics === null"># Loading status...</div>
        <div class="h5 mb-0 font-weight-bold" v-else-if="operational"># All systems operational</div>
        <div class="h5 mb-0 font-weight-bold text-danger" v-else># Some systems degraded</div>
      </div>

      <template v-if="statistics">
        <h3 class="font-weight-bold mb-4">Cluster Metrics</h3>

        <div class="row">
          <div class="col-12 col-md-3">
            <div class="card text-white bg-primary mb-4">
              <div class="card-body">
                <h4 class="card-title">{{ statistics.irc_messages_per_second }}</h4>
                <h5 class="card-text">IRC Messages/s</h5>
              </div>
            </div>
          </div>
          <div class="col-12 col-md-3">
            <div class="card text-white bg-primary-light-1 mb-4">
              <div class="card-body">
                <h4 class="card-title">{{ statistics.irc_commands_per_second }}</h4>
                <h5 class="card-text">IRC Commands/s</h5>
              </div>
            </div>
          </div>
          <div class="col-12 col-md-3">
            <div class="card text-white bg-primary-light-2 mb-4">
              <div class="card-body">
                <h4 class="card-title">{{ statistics.channels }}</h4>
                <h5 class="card-text">Channels</h5>
              </div>
            </div>
          </div>
          <div class="col-12 col-md-3">
            <div class="card text-white bg-primary-light-3 mb-4">
              <div class="card-body">
                <h4 class="card-title">{{ statistics.processes }}</h4>
                <h5 class="card-text">Processes</h5>
              </div>
            </div>
          </div>
        </div>

        <h3 class="font-weight-bold mb-4">Supervisors</h3>

        <div class="card mb-4" v-for="supervisor in statistics.supervisors">
          <div class="table-responsive">
            <table class="table mb-0">
              <thead class="thead-light">
              <tr>
                <th scope="col" colspan="5" class="border-0">
                  Supervisor <span class="d-none d-sm-inline">{{ supervisor.id }}</span><span
                    class="d-inline d-sm-none">{{ supervisor.id_short }}</span>
                </th>
              </tr>
              </thead>
              <thead class="thead-dark border-0">
              <tr class="d-none d-sm-table-row">
                <th scope="col"></th>
                <th scope="col">UUID</th>
                <th scope="col">State</th>
                <th scope="col">Last Ping</th>
                <th scope="col">Channels</th>
              </tr>
              </thead>
              <tbody>
              <tr v-for="process in supervisor.processes">
                <th scope="row" style="width: 30px; padding-right: 0;">
                  <i class="far fa-check-circle text-success" v-if="process.state === 'connected'"></i>
                  <i class="far fa-exclamation-triangle text-danger" v-else></i>
                </th>
                <th scope="row">{{ process.id_short }}</th>
                <td>{{ process.state }}</td>
                <td>{{ process.last_ping_at_in_seconds }}s</td>
                <td>{{ process.channels.length }}</td>
              </tr>
              </tbody>
            </table>
          </div>
        </div>
      </template>
    </div>
  </div>
</template>

<script>
export default {
  name: "TmiDashboard",

  props: {
    assetsUrl: String,
    dashboardUrl: String,
  },

  data() {
    return {
      statistics: null,
    }
  },

  mounted() {
    this.updateStatistics();
  },

  computed: {
    operational: function () {
      if (!this.statistics) return false;

      let operational = true;
      let processes = 0;

      this.statistics.supervisors.forEach(s => {
        s.processes.forEach(p => {
          if (p.state !== 'connected') {
            operational = false;
          } else {
            processes++;
          }
        });
      });

      // we need at least one process to be healthy
      operational = operational ? processes > 0 : false;

      this.updateIcon(operational);

      return operational;
    }
  },

  methods: {
    updateStatistics() {
      this.$http
          .post(`${this.dashboardUrl}/statistics`, this.statistics)
          .then(response => response.data)
          .then(data => this.statistics = data)
          .then(() => setTimeout(this.updateStatistics, 2500))
          .catch(error => console.error(error));
    },

    updateIcon(operational) {
      let link = document.querySelector("link[rel*='icon']") || document.createElement('link');
      link.type = 'image/x-icon';
      link.rel = 'shortcut icon';
      link.href = operational ? `${this.assetsUrl}/favicon.ico` : `${this.assetsUrl}/favicon-degraded.ico`;
      document.getElementsByTagName('head')[0].appendChild(link);
    }
  }
}
</script>

<style scoped>

</style>
