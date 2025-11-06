<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            {{ $this->getHeading() }}
        </x-slot>

        <div
            x-data="{
                chart: null,
                init() {
                    this.renderChart();

                    $wire.on('dateRangeUpdated', () => {
                        setTimeout(() => this.updateChart(), 100);
                    });
                },
                renderChart() {
                    const ctx = this.$refs.canvas.getContext('2d');
                    const data = @js($this->chartData);

                    this.chart = new Chart(ctx, {
                        type: 'doughnut',
                        data: {
                            labels: data.labels,
                            datasets: [{
                                data: data.data,
                                backgroundColor: [
                                    'rgb(59, 130, 246)',   // blue
                                    'rgb(34, 197, 94)',    // green
                                    'rgb(251, 146, 60)',   // orange
                                    'rgb(168, 85, 247)',   // purple
                                    'rgb(236, 72, 153)',   // pink
                                    'rgb(245, 158, 11)',   // amber
                                    'rgb(20, 184, 166)',   // teal
                                    'rgb(239, 68, 68)',    // red
                                    'rgb(139, 92, 246)',   // violet
                                    'rgb(14, 165, 233)',   // sky
                                ],
                                borderWidth: 2,
                                borderColor: '#fff'
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'right',
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            const value = context.parsed;
                                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                            const percentage = ((value / total) * 100).toFixed(1);
                                            return context.label + ': ₦' + value.toLocaleString() + ' (' + percentage + '%)';
                                        }
                                    }
                                }
                            }
                        }
                    });
                },
                updateChart() {
                    if (!this.chart) return;

                    $wire.call('getChartDataProperty').then(data => {
                        this.chart.data.labels = data.labels;
                        this.chart.data.datasets[0].data = data.data;
                        this.chart.update();
                    });
                }
            }"
        >
            <div class="relative" style="height: 300px;">
                <canvas x-ref="canvas"></canvas>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
