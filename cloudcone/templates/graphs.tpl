<link href="modules/servers/cloudcone/css/client.css" rel="stylesheet">
<script type="text/javascript" src="modules/servers/cloudcone/js/chart.bundle.min.js"></script>

<h2>
    <form method="post" action="clientarea.php?action=productdetails" style="display: inline-block;">
        <input type="hidden" name="id" value="{$serviceid}" />
        <button type="submit" class="btn btn-default">{$LANG.clientareabacklink}</button>
    </form>
    {$LANG.ccone.clientarea_graphs_title}
</h2>

<div class="ccone-chart" style="width:100%;height:300px">
    <canvas id="cpu-chart"></canvas>
</div>

<div class="ccone-chart" style="width:100%;height:300px">
    <canvas id="net-chart"></canvas>
</div>

<div class="ccone-chart" style="width:100%;height:300px">
    <canvas id="io-chart"></canvas>
</div>

{literal}
<script>
var cpu_graph = document.getElementById("cpu-chart");
var cpuline = new Chart(cpu_graph, {
    type: 'line',
    data: {
        labels: {/literal}{$graphs.cpu.time|json_encode}{literal},
        datasets: [{
            label: 'CPU Usage',
            data: {/literal}{$graphs.cpu.usage|json_encode}{literal},
            borderColor: '#e9aa99',
            backgroundColor: '#e9aa99',
            fill: false,
            pointRadius: 0,
            pointHoverRadius: 4,
        }]
    },
    options: {
        tooltips: {
            position: 'nearest',
            mode: 'index',
            intersect: false,
            callbacks: {
                label: function(tooltip, data) {
                    return data.datasets[tooltip.datasetIndex].label + ": " + tooltip.yLabel + '%';
                }
            }
        },
        title: {
            display: true,
            text: 'CPU Utilization',
            fontStyle: 'normal',
            fontSize: 20,
            fontColor: '#9A9A9A'
        },
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            xAxes: [{
                type: 'time',
                time: {
                    tooltipFormat: 'MMM D, h:mm a',
                    displayFormats: {
                        minute: 'h:mm a'
                    }
                }
            }],
            yAxes: [{
                ticks: {
                    beginAtZero: true
                },
                scaleLabel: {
                    display: true,
                    labelString: 'Percentage'
                }
            }]
        },
        hover: {
            mode: 'index',
            intersect: false
        }
    }
});

var net_graph = document.getElementById("net-chart");
var netline = new Chart(net_graph, {
    type: 'line',
    data: {
        labels: {/literal}{$graphs.time|json_encode}{literal},
        datasets: [{
            label: 'Inbound',
            data: {/literal}{$graphs.nin_mbps|json_encode}{literal},
            borderColor: '#b8d8d8',
            backgroundColor: '#b8d8d8',
            fill: false,
            pointRadius: 0,
            pointHoverRadius: 4,
        },
        {
            label: 'Outbound',
            data: {/literal}{$graphs.nout_mbps|json_encode}{literal},
            borderColor: '#c9de8a',
            backgroundColor: '#c9de8a',
            fill: false,
            pointRadius: 0,
            pointHoverRadius: 4,
        }]
    },
    options: {
        tooltips: {
            callbacks: {
                label: function(tooltip, data) {
                    return data.datasets[tooltip.datasetIndex].label + ": " + tooltip.yLabel + ' Mbps';
                }
            },
            position: 'nearest',
            mode: 'index',
            intersect: false
        },
        title: {
            display: true,
            text: 'Network traffic throughput',
            fontStyle: 'normal',
            fontSize: 20,
            fontColor: '#9A9A9A'
        },
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            xAxes: [{
                type: 'time',
                time: {
                    tooltipFormat: 'MMM D, h:mm a',
                    displayFormats: {
                        minute: 'h:mm a'
                    }
                }
            }],
            yAxes: [{
                ticks: {
                    beginAtZero: true
                },
                scaleLabel: {
                    display: true,
                    labelString: 'Megabytes / sec'
                }
            }]
        },
        hover: {
            mode: 'index',
            intersect: false
        }
    }
});

var io_graph = document.getElementById("io-chart");
var netline = new Chart(io_graph, {
    type: 'line',
    data: {
        labels: {/literal}{$graphs.time|json_encode}{literal},
        datasets: [{
            label: 'Read/s',
            data: {/literal}{$graphs.dread_mbps|json_encode}{literal},
            borderColor: '#baa9ba',
            backgroundColor: '#baa9ba',
            fill: false,
            pointRadius: 0,
            pointHoverRadius: 4,
        },
        {
            label: 'Write/s',
            data: {/literal}{$graphs.dwrite_mbps|json_encode}{literal},
            borderColor: '#e9d69d',
            backgroundColor: '#e9d69d',
            fill: false,
            pointRadius: 0,
            pointHoverRadius: 4,
        }]
    },
    options: {
        tooltips: {
            position: 'nearest',
            mode: 'index',
            intersect: false,
            callbacks: {
                label: function(tooltip, data) {
                    return data.datasets[tooltip.datasetIndex].label + ": " + tooltip.yLabel + ' Mbps';
                }
            }
        },
        title: {
            display: true,
            text: 'IOPS traffic throughput',
            fontStyle: 'normal',
            fontSize: 20,
            fontColor: '#9A9A9A'
        },
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            xAxes: [{
                type: 'time',
                time: {
                    tooltipFormat: 'MMM D, h:mm a',
                    displayFormats: {
                        minute: 'h:mm a'
                    }
                }
            }],
            yAxes: [{
                ticks: {
                    beginAtZero: true
                },
                scaleLabel: {
                    display: true,
                    labelString: 'Megabytes / sec'
                }
            }]
        },
        hover: {
            mode: 'index',
            intersect: false
        }
    }
});
</script>
{/literal}
