<template>
  <div class="chart-container mb-4">
    <canvas id="canvas"></canvas>
  </div>
</template>

<script>
export default {
  name: "Charts",
  data() {
    return {
      config: {
        type: 'line',
        data: {
          labels: ['','','','','','','','','','','','','','','','','','','',''],
          datasets: [{
            field: 'irc_messages_per_second',
            label: 'Messages per Seconds',
            backgroundColor: window.chartBackgroundColors.color_1,
            borderColor: window.chartColors.color_1,
            data: [0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0],
          }, {
            field: 'irc_commands_per_second',
            label: 'Commands per Seconds',
            backgroundColor: window.chartBackgroundColors.color_1,
            borderColor: window.chartColors.color_2,
            data: [0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0],
          }]
        },
        options: {
          maintainAspectRatio: false,
          tooltips: {
            mode: 'index',
            intersect: false,
          },
          hover: {
            mode: 'nearest',
            intersect: true
          },
          scales: {
            xAxes: [{
              display: false,
              scaleLabel: {
                display: false
              }
            }],
            yAxes: [{
              stacked: true,
              display: true,
              scaleLabel: {
                display: true,
                labelString: 'Commands & Messages'
              },
              ticks: {
                suggestedMin: 0
              }
            }]
          }
        }
      },
      myLine: null,
    };
  },
  mounted() {
    let ctx = document.getElementById('canvas').getContext('2d');
    this.myLine = new Chart(ctx, this.config);
  },
  methods: {
    addData(data) {
      console.log(data);
      this.config.data.labels.push('');
      this.config.data.datasets.forEach((dataset) => {
        console.log(data[dataset.field], dataset.label);
        dataset.data.push(data[dataset.field]);
      });

      if (this.config.data.labels.length > 20) {
        this.removeOldestItem();
      }

      this.myLine.update();
    },
    removeOldestItem() {
      this.config.data.labels.shift(); // remove the label first

      this.config.data.datasets.forEach(function (dataset) {
        dataset.data.shift();
      });
    }
  }
}
</script>

<style scoped>
.chart-container {
  position: relative;
  margin: auto;
  height: 200px;
}
</style>