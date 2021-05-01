<template>
  <div>
    <div class="bg-gradient-tmi text-white mb-3">
      <div class="py-5 container">
        <div class="d-flex pb-4 justify-content-between">
          <div>
            <h1 class="fw-bold">TMI Cluster</h1>
            <h3 class="mb-0 text-white-50">Service Status</h3>
          </div>
          <div class="lead mb-0 align-self-center">
            <span class="d-none d-sm-inline">
              <a href="https://tmiphp.com" target="_blank" class="text-white-50 text-decoration-none">
                Powered by TMI.php
              </a>
            </span>
          </div>
        </div>
      </div>
    </div>

    <div class="container">
      <div class="alert alert-primary bg-white mb-4 py-4" style="margin-top: -55px;">
        <div class="h5 mb-0 fw-bold" v-if="statistics === null"># Loading status...</div>
        <div class="h5 mb-0 fw-bold" v-else-if="operational"># All systems operational</div>
        <div class="h5 mb-0 fw-bold text-danger" v-else># Some systems degraded</div>
      </div>

      <template v-if="statistics">
        <h3 class="fw-bold mb-4">Cluster Metrics</h3>

        <div class="row">
          <div class="col-12 col-md-3">
            <div class="card text-white bg-primary mb-4">
              <div class="card-body">
                <h4 class="card-title">{{ statistics.irc_messages_per_second|formatNumber }}</h4>
                <h5 class="card-text">IRC Messages/s</h5>
              </div>
            </div>
          </div>
          <div class="col-12 col-md-3">
            <div class="card text-white bg-primary-light-1 mb-4">
              <div class="card-body">
                <h4 class="card-title">{{ statistics.irc_commands_per_second|formatNumber }}</h4>
                <h5 class="card-text">IRC Commands/s</h5>
              </div>
            </div>
          </div>
          <div class="col-12 col-md-3">
            <div class="card text-white bg-primary-light-2 mb-4">
              <div class="card-body">
                <h4 class="card-title">{{ statistics.channels|formatNumber }}</h4>
                <h5 class="card-text">Channels</h5>
              </div>
            </div>
          </div>
          <div class="col-12 col-md-3">
            <div class="card text-white bg-primary-light-3 mb-4">
              <div class="card-body">
                <h4 class="card-title">{{ statistics.processes|formatNumber }}</h4>
                <h5 class="card-text">Processes</h5>
              </div>
            </div>
          </div>
        </div>

        <div class="row">
          <div class="col-12 col-md-6">
            <div class="card text-white bg-primary mb-4">
              <div class="card-body">
                <h4 class="card-title">{{ statistics.irc_messages|formatNumber }}</h4>
                <h5 class="card-text">IRC Messages Processed</h5>
              </div>
            </div>
          </div>
          <div class="col-12 col-md-6">
            <div class="card text-white bg-primary-light-1 mb-4">
              <div class="card-body">
                <h4 class="card-title">{{ statistics.irc_commands|formatNumber }}</h4>
                <h5 class="card-text">IRC Commands Processed</h5>
              </div>
            </div>
          </div>
        </div>

        <charts ref="charts"></charts>

        <h3 class="fw-bold mb-4">Search</h3>

        <form @submit.prevent="search">
          <div class="mb-4">
            <label for="searchQuery" class="form-label d-none">Username</label>
            <input type="text" class="form-control" id="searchQuery" aria-describedby="searchHelp"
                   autocomplete="off" @keyup="search" v-model="q">
            <div id="searchHelp" class="form-text">
              Enter a channel name you want to search.
              The results are cached until the page is refreshed.
            </div>
          </div>
        </form>

        <div class="card mb-4" v-if="results">
          <div class="table-responsive">
            <table class="table table-striped mb-0">
              <thead class="thead-primary">
              <tr>
                <th scope="col" colspan="5" class="border-0">
                  Search Results
                  <span class="d-none d-sm-inline"> for "{{ results.query }}"</span>
                </th>
              </tr>
              </thead>
              <thead class="thead-secondary border-0">
              <tr class="d-none d-sm-table-row">
                <th scope="col"></th>
                <th scope="col">User</th>
                <th scope="col">State</th>
                <th scope="col">Last Ping</th>
                <th scope="col">Supervisor / Process</th>
              </tr>
              </thead>
              <tbody>
              <tr v-for="result in results.data">
                <th scope="row" style="width: 30px; padding-right: 0;">
                  <img :src="result.avatar_url" class="img-fluid rounded-circle float-start"
                       alt="">
                </th>
                <th scope="row">{{ result.channel }}</th>
                <td>{{ result.process.state }}</td>
                <td>{{ result.process.last_ping_at_in_seconds }}s</td>
                <td>
                  <span class="badge rounded-pill bg-success">
                      {{ result.process.supervisor_id }}
                  </span>
                  <span
                      :class="['badge rounded-pill', result.process.state === 'connected' ? 'bg-success' : 'bg-danger']">
                      {{ result.process.id_short }}
                  </span>
                </td>
              </tr>
              </tbody>
            </table>
          </div>
        </div>

        <h3 class="fw-bold mb-4">Supervisors</h3>

        <div class="card mb-4" v-for="supervisor in statistics.supervisors">
          <div class="table-responsive">
            <table class="table table-striped mb-0">
              <thead class="thead-primary">
              <tr>
                <th scope="col" colspan="5" class="border-0">
                  Supervisor
                  <span class="d-none d-sm-inline">{{ supervisor.id }}</span>
                  <span class="d-inline d-sm-none">{{ supervisor.id_short }}</span>
                </th>
              </tr>
              </thead>
              <thead class="thead-secondary border-0">
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
                  <i class="far fa-check-circle text-success"
                     v-if="process.state === 'connected'"></i>
                  <i class="far fa-exclamation-triangle text-danger" v-else></i>
                </th>
                <th scope="row">{{ process.id_short }}</th>
                <td>{{ process.state }}</td>
                <td>{{ process.last_ping_at_in_seconds }}s</td>
                <td>{{ process.metrics.channels|formatNumber }}</td>
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
import Charts from "./Charts";

export default {
  name: "TmiDashboard",
  components: {Charts},
  props: {
    assetsUrl: String,
    dashboardUrl: String,
  },

  data() {
    return {
      channel_statistics: null,
      statistics: null,
      results: null,
      q: '',
    }
  },

  mounted() {
    this.updateStatistics();
  },

  computed: {
    operational: function () {
      if (!this.statistics) return false;
      this.updateIcon(this.statistics.operational);
      return this.statistics.operational;
    }
  },

  methods: {
    search() {
      if (this.q.length <= 0) {
        this.results = null;
        return;
      }

      this.results = {
        query: this.q,
        data: this.channel_statistics.supervisors.map(supervisor => {
          return supervisor.processes.map(process => {
            return process.channels.map(channel => {
              if (channel.includes(this.q)) {
                channel = channel.replace('#', '');
                return {
                  avatar_url: 'https://own3d.pro/api/v1/resolvers/avatars/twitch/' + channel,
                  channel,
                  process: {
                    state: process.state,
                    id_short: process.id_short,
                    supervisor_id: process.supervisor_id,
                    last_ping_at_in_seconds: process.last_ping_at_in_seconds,
                  },
                };
              }
              return null;
            });
          });
        }).flat(2).filter(x => x).slice(0, 25)
      };
    },

    updateStatistics() {
      this.$http
          .post(`${this.dashboardUrl}/statistics`, this.statistics)
          .then(response => response.data)
          .then(data => {
            if (!this.channel_statistics) {
              this.channel_statistics = data;
            }
            this.statistics = data;

            if (this.$refs.charts) {
              this.$refs.charts.addData({
                irc_messages_per_second: data.irc_messages_per_second,
                irc_commands_per_second: data.irc_commands_per_second,
              });
            }
          })
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
.table > tbody > tr > td {
  vertical-align: middle;
}
</style>
