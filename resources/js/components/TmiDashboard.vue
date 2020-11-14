<template>
    <div v-if="statistics">
        <div class="row">
            <div class="col-12 col-md-3">
                <div class="card text-white bg-primary mb-3">
                    <div class="card-body">
                        <h4 class="card-title">{{ statistics.irc_messages_per_second }}</h4>
                        <h5 class="card-text">Messages/s</h5>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-3">
                <div class="card text-white bg-info mb-3">
                    <div class="card-body">
                        <h4 class="card-title">{{ statistics.irc_commands_per_second }}</h4>
                        <h5 class="card-text">Commands/s</h5>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-3">
                <div class="card text-white bg-warning mb-3">
                    <div class="card-body">
                        <h4 class="card-title">{{ statistics.channels }}</h4>
                        <h5 class="card-text">Channels</h5>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-3">
                <div class="card text-white bg-danger mb-3">
                    <div class="card-body">
                        <h4 class="card-title">{{ statistics.processes }}</h4>
                        <h5 class="card-text">Processes</h5>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3" v-for="supervisor in statistics.supervisors">
            <table class="table mb-0">
                <thead class="thead-dark">
                <tr>
                    <th scope="col" colspan="5">
                        Supervisor {{ supervisor.id }}
                    </th>
                </tr>
                </thead>
                <thead class="thead-light">
                <tr>
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
                    <td>{{ process.last_ping_at_in_seconds }}</td>
                    <td>{{ process.channels.length }}</td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script>
export default {
    name: "TmiDashboard",

    props: {
        statisticsUrl: String,
    },

    data() {
        return {
            statistics: null,
        }
    },

    mounted() {
        this.updateStatistics();
    },

    methods: {
        updateStatistics() {
            this.$http
                .post(this.statisticsUrl, this.statistics)
                .then(response => response.data)
                .then(data => this.statistics = data)
                .then(() => setTimeout(this.updateStatistics, 2500))
                .catch(error => console.error(error));
        }
    }
}
</script>

<style scoped>

</style>
