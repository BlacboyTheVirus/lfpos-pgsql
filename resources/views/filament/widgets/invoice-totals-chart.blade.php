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
                        type: 'bar',
                        data: {
                            labels: data.labels,
                            datasets: [
                                {
                                    label: 'Paid Amount',
                                    data: data.paid,
                                    backgroundColor: 'rgb(34, 197, 94)',
                                    borderColor: 'rgb(34, 197, 94)',
                                    borderWidth: 1
                                },
                                {
                                    label: 'Due Amount',
                                    data: data.due,
                                    backgroundColor: 'rgb(251, 146, 60)',
                                    borderColor: 'rgb(251, 146, 60)',
                                    borderWidth: 1
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            interaction: {
                                mode: 'index',
                                intersect: false,
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        callback: function(value) {
                                            return '₦' + value.toLocaleString();
                                        }
                                    }
                                }
                            },
                            plugins: {
                                legend: {
                                    position: 'top',
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            let label = context.dataset.label || '';
                                            if (label) {
                                                label += ': ';
                                            }
                                            label += '₦' + context.parsed.y.toLocaleString();
                                            return label;
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
                        this.chart.data.datasets[0].data = data.paid;
                        this.chart.data.datasets[1].data = data.due;
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

    @once
        @push('scripts')
            <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
        @endpush
    @endonce
</x-filament-widgets::widget>
