$(document).ready(function () {
    const templates = {};
    Object.defineProperty(templates, 'panelTemplate', {
        value: '<div class="row mb-2">\n' +
        '    <div class="col">\n' +
        '        <div class="card">\n' +
        '            <div class="card-header text-white" style="background-color: #337ab7">\n' +
        '                <div class="text-center">\n' +
        '                    <strong class="header-nazov">Operátori</strong>\n' +
        '                    <span class="header-zmena ml-1">[Poobedná zmena]</span>\n' +
        '                </div>\n' +
        '            </div>\n' +
        '            <div class="card-body">\n' +
        '                <div class="row">\n' +
        '                    <div class="col col-md-6">\n' +
        '                        <div class="row mb-2">\n' +
        '                            <div class="col">\n' +
        '                                <strong>Medián času cyklu [s]</strong>\n' +
        '                            </div>\n' +
        '                        </div>\n' +
        '\n' +
        '                        <div class="row">\n' +
        '                            <div class="col">\n' +
        '                                <div class="chartWrapper">\n' +
        '                                    <div class="chart-container">\n' +
        '                                        <canvas class="opTcyCnv"></canvas>\n' +
        '                                    </div>\n' +
        '                                </div>\n' +
        '                            </div>\n' +
        '                        </div>\n' +
        '                    </div>\n' +
        '\n' +
        '                    <div class="col col-md-6">\n' +
        '                        <div class="row mb-2">\n' +
        '                            <div class="col">\n' +
        '                                <strong>Počet prekročení času cyklu</strong>\n' +
        '                            </div>\n' +
        '                        </div>\n' +
        '\n' +
        '                        <div class="row">\n' +
        '                            <div class="col">\n' +
        '                                <div class="chartWrapper">\n' +
        '                                    <div class="chart-container">\n' +
        '                                        <canvas class="opOverCnv"></canvas>\n' +
        '                                    </div>\n' +
        '                                </div>\n' +
        '                            </div>\n' +
        '                        </div>\n' +
        '                    </div>             \n' +
        '                </div>\n' +
        '            </div>\n' +
        '        </div>\n' +
        '    </div>\n' +
        '</div>',
        writable: false
    });

    Object.defineProperty(templates, 'oknoTemplate', {
        value: '<div class="row mb-5">\n' +
        '    <div class="col">\n' +
        '        <div class="row mb-1">\n' +
        '            <div class="col text-center">\n' +
        '                <strong class="okno-header"></strong>\n' +
        '                <span class="okno-info ml-2"></span>\n' +
        '            </div>\n' +
        '        </div>\n' +
        '        \n' +
        '        <div class="row">\n' +
        '            <div class="col">\n' +
        '                <div class="chartWrapper-okno">\n' +
        '                    <div class="chart-container-okno">\n' +
        '                        <canvas></canvas>\n' +
        '                </div>\n' +
        '            </div>\n' +
        '        </div>\n' +
        '    </div>\n' +
        '</div>',
        writable: false
    });


    let datePicker = $('#datePicker');
    let zmenaBtn = $('.zmenaBtn');
    let analyseBtn = $('#analyseBtn');
    let preloader = $("#preloader");

    datePicker.daterangepicker({
        autoUpdateInput: false,
        timePicker: false,
        showWeekNumbers: true,
        showISOWeekNumbers: true,
        singleDatePicker: true,
        opens: 'center',
        locale: {
            format: 'DD/MM/YYYY',
            weekLabel: "T",
            applyLabel: 'Použiť',
            cancelLabel: 'Vymazať',
            daysOfWeek: ["Ne", "Po", "Ut", "St", "Št", "Pia", "So"],
            monthNames: ["Január", "Február", "Marec", "Apríl", "Máj", "Jún",
                "Júl", "August", "September", "Október", "November", "December"
            ],
            firstDay: 1
        }
    });

    datePicker.on('apply.daterangepicker', function (ev, picker) {
        $(this).val(picker.startDate.format('DD/MM/YYYY'));

        if (zmenaBtn.hasClass('active') === true) {
            analyseBtn.attr('disabled', false);
        }
    });

    datePicker.on('hide.daterangepicker', function (ev, picker) {
        $(this).blur();
    });

    $('#clearDate').click(function () {
        $(this).blur();
        datePicker.val("");
    });

    $('#linkaSelectBtn').change(function () {
        $(this).blur();
    });

    $('.linkaSelectItem').click(function () {
        $(this).blur();

        $('.linkaSelectItem').removeClass('active');
        $(this).toggleClass('active');

        $('#linkaBtnText').text($(this).text());
    });

    zmenaBtn.click(function () {
        $(this).blur();
        $(this).toggleClass('active');

        if (zmenaBtn.hasClass('active') === true && datePicker.val().length > 0) {
            analyseBtn.attr('disabled', false);
        } else {
            analyseBtn.attr('disabled', true);
        }
    });

    analyseBtn.click(function () {
        $(this).blur();

        let linka = $('.linkaSelectItem.active').data('id');
        let zmeny = [];

        $.map($('.zmenaBtn.active'), function (val) {
            zmeny.push($(val).text());
        });
        let data = {
            'datum': datePicker.val(),
            'zmeny': zmeny,
            'trojzmenka': !$('#zmenaTgl')[0].checked
        };

        $('main').empty();
        preloader.show();
        $.post("/analyse/" + linka, data, function (data) {
            preloader.hide();

            data.forEach(function (item) {
                let panel = addPanel(item);

                createOperatorsChart(item.operatori, item.linka, item.zmena);

                item.okna.forEach(function (okno) {
                    if (okno.vals.length === 0)
                        return;

                    let oknoDiv = addOknoPanel(okno, panel);
                    createOknoChart(okno, oknoDiv);
                });
            });

            // sessionStorage.setItem('panelObjects', JSON.stringify(window.chartsInfo));
        }).fail(function () {
            preloader.hide();
            alert('Nastala chyba pri nacitavani udajov!');
        });
    });

    function addPanel(item) {
        let panel = $(templates.panelTemplate).appendTo("main");

        panel.find('.header-nazov').text(item.linka);
        panel.find('.header-zmena').text('[' + item.zmenaString + ']');
        panel.find('.opTcyCnv').attr('id', item.linka + '_' + item.zmena + '_opTcy');
        panel.find('.opOverCnv').attr('id', item.linka + '_' + item.zmena + '_opOver');

        return panel;
    }

    function addOknoPanel(oknoData, panel) {
        let card = panel.find('.card-body');
        let okno = $(templates.oknoTemplate).appendTo(card);

        okno.find('.okno-header').text(oknoData.okno);
        okno.find('.okno-info').text("[ Min: " + oknoData.min + " , Max: " + oknoData.max + ", Stred: " + oknoData.stred + "]");


        return okno;
    }

    function createOperatorsChart(operatori, linka, zmena) {
        let id_1 = '#' + linka + '_' + zmena + '_opTcy';
        let id_2 = '#' + linka + '_' + zmena + '_opOver';

        window.charts = new Chart($(id_1), {
            type: 'bar',
            data: {
                labels: operatori.tcy.labels,
                datasets: [
                    {
                        label: "Median",
                        backgroundColor: "rgba(54, 162, 235, 0.2)",
                        borderColor: "rgb(54, 162, 235)",
                        borderWidth: 1,
                        lineTension: 0,
                        data: operatori.tcy.vals,
                        datalabels: {
                            align: 'top',
                            anchor: 'start'
                        }
                    }
                ]
            },
            options: {
                animation: false,
                responsive: true,
                maintainAspectRatio: false,
                legend: {
                    display: false
                },
                scales: {
                    xAxes: [{
                        gridLines: {
                            drawOnChartArea: false
                        }
                    }],
                    yAxes: [{
                        display: false,
                        ticks: {
                            beginAtZero: true,
                            max: (operatori.tcy.max + 3)
                        },
                        gridLines: {
                            drawOnChartArea: false
                        }
                    }]
                },
                plugins: {
                    datalabels: {
                        color: 'black',
                        display: function (context) {
                            return context.dataset.data[context.dataIndex] > 0;
                        },
                        font: {
                            weight: 'bold'
                        },
                        formatter: Math.round
                    }
                }
            }
        });

        window.charts = new Chart($(id_2), {
            type: 'bar',
            data: {
                labels: operatori.overtimes.labels,
                datasets: [
                    {
                        label: "Prekročenia",
                        backgroundColor: "rgba(54, 162, 235, 0.2)",
                        borderColor: "rgb(54, 162, 235)",
                        borderWidth: 1,
                        lineTension: 0,
                        data: operatori.overtimes.vals,
                        datalabels: {
                            align: 'top',
                            anchor: 'start'
                        }
                    }
                ]
            },
            options: {
                animation: false,
                responsive: true,
                maintainAspectRatio: false,
                legend: {
                    display: false
                },
                scales: {
                    xAxes: [{
                        gridLines: {
                            drawOnChartArea: false
                        }
                    }],
                    yAxes: [{
                        display: false,
                        ticks: {
                            beginAtZero: true,
                            max: (operatori.overtimes.max + 3)
                        },
                        gridLines: {
                            drawOnChartArea: false
                        }
                    }]
                },
                plugins: {
                    datalabels: {
                        color: 'black',
                        display: function (context) {
                            return context.dataset.data[context.dataIndex] > 0;
                        },
                        font: {
                            weight: 'bold'
                        },
                        formatter: Math.round
                    }
                }
            }
        });
    }

    function createOknoChart(oknoData, oknoDiv) {
        let canvas = oknoDiv.find('canvas');

        let tempData = {
            labels: oknoData.labels,
            datasets: [{
                label: "Cas otvorenia [s]",
                fill: false,
                borderColor: '#337ab7',
                borderWidth: 2,
                lineTension: 0,
                data: oknoData.vals,
                datalabels: {
                    display: false
                }
            }, {
                label: "Ciel",
                fill: false,
                borderColor: 'red',
                borderWidth: 1,
                lineTension: 0,
                data: oknoData.ciel,
                datalabels: {
                    display: false
                }
            }, {
                label: "Median",
                fill: false,
                borderColor: 'orange',
                borderWidth: 1,
                lineTension: 0,
                data: oknoData.median,
                datalabels: {
                    display: false
                }
            }]
        };

        window.charts = new Chart(canvas, {
            type: 'line',
            data: tempData,
            options: {
                animation: false,
                responsive: true,
                maintainAspectRatio: false,
                legend: {
                    display: false
                },
                tooltips: {
                    callbacks: {
                        title: function (tooltipItems) {
                            return moment(tooltipItems[0].xLabel).format('DD/MM/YYYY HH:mm');
                        }
                    }
                },
                scales: {
                    xAxes: [{
                        type: 'time',
                        time: {
                            unit: 'minute',
                            stepSize: 15,
                            displayFormats: {
                                'minute': 'HH:mm'
                            }
                        },
                        gridLines: {
                            drawOnChartArea: false
                        }
                    }],
                    yAxes: [{
                        ticks: {
                            beginAtZero: true
                            // max: oknoData.max + 2
                        },
                        gridLines: {
                            drawOnChartArea: false
                        }
                    }]
                },
                elements: {
                    point: {
                        radius: 0
                    }
                }
            }
        });
    }
});
