<template>
  <div class="dashboard-card">
    <div class="chart-container" v-if="!loading">
      <div 
        ref="chartRef" 
        class="chart-wrapper"
        style="width: 100%; height: 400px;"
      ></div>
    </div>

    <div v-else class="chart-placeholder">
      <el-skeleton animated>
        <template #template>
          <el-skeleton-item variant="rect" style="height: 400px" />
        </template>
      </el-skeleton>
    </div>
  </div>
</template>

<script>
import * as echarts from 'echarts';

export default {
  name: 'LineChart',
  props: {
    chartData: {
      type: Array,
      default: () => []
    },
    loading: {
      type: Boolean,
      default: false
    }
  },
  data() {
    return {
      chartInstance: null
    }
  },
  mounted() {
    if (!this.loading && this.chartData.length > 0) {
      this.initChart();
    }
  },
  watch: {
    chartData: {
      handler(newData) {
        if (newData && newData.length > 0 && !this.loading) {
          this.updateChart();
        }
      },
      deep: true
    },
    loading(newVal) {
      if (!newVal && this.chartData.length > 0) {
        this.$nextTick(() => {
          this.initChart();
        });
      }
    }
  },
  beforeUnmount() {
    if (this.chartInstance) {
      this.chartInstance.dispose();
    }
  },
  methods: {
    initChart() {
      if (this.chartInstance) {
        this.chartInstance.dispose();
      }
      
      this.$nextTick(() => {
        if (this.$refs.chartRef) {
          this.chartInstance = echarts.init(this.$refs.chartRef);
          this.updateChart();
        }
      });
    },
    
    updateChart() {
      if (!this.chartInstance || !this.chartData.length) return;

      const option = {
        tooltip: {
          trigger: 'axis',
          backgroundColor: 'var(--fluentshipment-primary-bg)',
          borderColor: 'var(--fluentshipment-primary-border)',
          borderWidth: 1,
          textStyle: {
            color: 'var(--fluentshipment-title-text-color)'
          }
        },
        legend: {
          data: ['Shipments', 'Orders'],
          top: '5%',
          textStyle: {
            color: 'var(--fluentshipment-title-text-color)'
          }
        },
        grid: {
          left: '3%',
          right: '4%',
          bottom: '3%',
          containLabel: true
        },
        xAxis: {
          type: 'category',
          boundaryGap: false,
          data: this.chartData.map(item => item.date),
          axisLabel: {
            color: 'var(--fluentshipment-secondary-text)',
            fontSize: 12
          },
          axisLine: {
            lineStyle: {
              color: 'var(--fluentshipment-primary-border)'
            }
          }
        },
        yAxis: {
          type: 'value',
          axisLabel: {
            color: 'var(--fluentshipment-secondary-text)',
            fontSize: 12
          },
          splitLine: {
            show: true,
            lineStyle: {
              color: 'var(--fluentshipment-primary-border)',
              type: 'dashed'
            }
          }
        },
        series: [
          {
            name: 'Shipments',
            type: 'line',
            smooth: true,
            data: this.chartData.map(item => item.shipments),
            lineStyle: {
              color: '#3b82f6',
              width: 3
            },
            areaStyle: {
              color: {
                type: 'linear',
                x: 0,
                y: 0,
                x2: 0,
                y2: 1,
                colorStops: [
                  {
                    offset: 0,
                    color: 'rgba(59, 130, 246, 0.3)'
                  },
                  {
                    offset: 1,
                    color: 'rgba(59, 130, 246, 0.1)'
                  }
                ]
              }
            },
            symbol: 'circle',
            symbolSize: 6,
            itemStyle: {
              color: '#3b82f6'
            }
          },
          {
            name: 'Orders',
            type: 'line',
            smooth: true,
            data: this.chartData.map(item => item.orders),
            lineStyle: {
              color: '#10b981',
              width: 3
            },
            areaStyle: {
              color: {
                type: 'linear',
                x: 0,
                y: 0,
                x2: 0,
                y2: 1,
                colorStops: [
                  {
                    offset: 0,
                    color: 'rgba(16, 185, 129, 0.3)'
                  },
                  {
                    offset: 1,
                    color: 'rgba(16, 185, 129, 0.1)'
                  }
                ]
              }
            },
            symbol: 'circle',
            symbolSize: 6,
            itemStyle: {
              color: '#10b981'
            }
          }
        ]
      };

      this.chartInstance.setOption(option);
    }
  }
}
</script>

<style scoped>
.chart-wrapper {
  min-height: 400px;
}
</style>
