$(function(){
    const labels = porcentajeEstados.map( d => { return d.estado; });
    
    dataset1 = porcentajeEstados.map( d => { return d.total_prevencion; });
    dataset2 = porcentajeEstados.map( d => { return d.total_ayuda; });
    
    const data = {
    labels: labels,
    datasets: [
        {
            label: 'Candidaturas de Prevención',
            backgroundColor: '#c09c65',
            borderColor: 'rgb(255, 99, 132)',
            data: dataset1,
            maxBarThickness: 50,
            barPercentage : 0.5,
            categoryPercentage : 0.5
        },
        {
            label: 'Candidaturas de Ayuda',
            backgroundColor: '#2196f3',
            borderColor: 'rgb(255, 99, 200)',
            data: dataset2,
            maxBarThickness: 50,
            barPercentage : 0.5,
            categoryPercentage : 0.5
        }
    ]
    };
    const config = {
        type: 'bar',
        data: data,
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        font: {
                            family: "Montserrat",
                            size: 14
                        }
                    }
                },
                title: {
                    display: false,
                    text: 'Estados participantes',
                    font: {
                        family: "Montserrat",
                        weight: "bold"
                    }
                }
            },
            scales: {
                y: {
                    min: 0,
                    type: 'linear',
                    ticks: {
                        stepSize: 1
                    },
                },
                x: {
                    grid: {
                        display: false
                    },
                }
            },
            layout: {
                padding: 20
            }
        },
    };
    if (porcentajeEstados.length == 0) {
        $('#barras-estado').siblings('p').show();
    }
    else {
        var myChart = new Chart(
            document.getElementById('barras-estado'),
            config
        );
    }
})