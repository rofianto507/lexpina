  var isRTL = JSON.parse(localStorage.getItem('isRTL'));
      if (isRTL) {
        var linkDefault = document.getElementById('style-default');
        var userLinkDefault = document.getElementById('user-style-default');
        linkDefault.setAttribute('disabled', true);
        userLinkDefault.setAttribute('disabled', true);
        document.querySelector('html').setAttribute('dir', 'rtl');
      } else {
        var linkRTL = document.getElementById('style-rtl');
        var userLinkRTL = document.getElementById('user-style-rtl');
        linkRTL.setAttribute('disabled', true);
        userLinkRTL.setAttribute('disabled', true);
      }
document.addEventListener("DOMContentLoaded", function() {
    
    // ==========================================
    // 1. CHART DONAT (TRANSAKSI PER PRODUK)
    // ==========================================
    var donutDom = document.getElementById('trans_by_product_chart');
    var donutChart = null;
    
    if (donutDom) {
        donutChart = echarts.init(donutDom);
        var donutOption = {
            tooltip: {
                trigger: 'item',
                formatter: '{a} <br/>{b}: {c} ({d}%)'
            },
            legend: {
                bottom: '5%',
                left: 'center'
            },
            color: ['#2c3e50', '#f1c40f', '#e74c3c', '#3498db', '#2ecc71'], // Palet warna elegan
            series: [
                {
                    name: 'Transaksi',
                    type: 'pie',
                    radius: ['45%', '70%'], // Membuat lubang di tengah (Donut)
                    center: ['50%', '45%'], // Digeser sedikit ke atas untuk ruang legend
                    avoidLabelOverlap: false,
                    itemStyle: {
                        borderRadius: 8,
                        borderColor: '#f9fafd', // Warna background card body (bg-light)
                        borderWidth: 2
                    },
                    label: {
                        show: false,
                        position: 'center'
                    },
                    emphasis: {
                        label: {
                            show: true,
                            fontSize: '18',
                            fontWeight: 'bold'
                        }
                    },
                    labelLine: {
                        show: false
                    },
                    data: chartDataProduk // Data dari PHP
                }
            ]
        };
        donutChart.setOption(donutOption);
    }

    // ==========================================
    // 2. CHART BATANG (DOKUMEN TERPOPULER)
    // ==========================================
    var barDom = document.getElementById('document_populer_chart');
    var barChart = null;

    if (barDom) {
        barChart = echarts.init(barDom);
        var barOption = {
            tooltip: {
                trigger: 'axis',
                axisPointer: { type: 'shadow' }
            },
            grid: {
                left: '3%',
                right: '4%',
                bottom: '10%', // Memberikan ruang lebih di bawah untuk teks
                top: '8%',
                containLabel: true
            },
            xAxis: {
                type: 'category',
                data: chartLabelPopuler, // Data Judul dari PHP
                axisLabel: {
                    interval: 0,
                    rotate: 30, // Rotasi teks agar tidak menumpuk
                    fontSize: 11,
                    color: '#5e6e82'
                },
                axisLine: {
                    lineStyle: { color: '#d8e2ef' }
                }
            },
            yAxis: {
                type: 'value',
                axisLabel: { color: '#5e6e82' },
                splitLine: {
                    lineStyle: { color: '#d8e2ef', type: 'dashed' }
                }
            },
            series: [
                {
                    name: 'Total Views',
                    type: 'bar',
                    barWidth: '40%',
                    data: chartDataPopuler, // Data Views dari PHP
                    itemStyle: {
                        color: '#3498db', // Warna biru primer
                        borderRadius: [4, 4, 0, 0] // Melengkung di bagian atas saja
                    }
                }
            ]
        };
        barChart.setOption(barOption);
    }
    // ==========================================
    // 3. CHART GARIS (TREN VISITOR BULAN INI)
    // ==========================================
    var visitorDom = document.getElementById('visitor_chart');
    var visitorChart = null;

    if (visitorDom) {
        visitorChart = echarts.init(visitorDom);
        var visitorOption = {
            tooltip: {
                trigger: 'axis',
                axisPointer: {
                    type: 'line',
                    lineStyle: {
                        color: '#d8e2ef',
                        type: 'dashed'
                    }
                }
            },
            grid: {
                left: '2%',
                right: '3%',
                bottom: '8%',
                top: '10%',
                containLabel: true
            },
            xAxis: [
                {
                    type: 'category',
                    boundaryGap: false, // Membuat garis menyentuh ujung area grafik
                    data: chartLabelVisitor, // Tanggal 01 Apr - 30 Apr
                    axisLabel: { color: '#5e6e82', fontSize: 11 },
                    axisLine: { lineStyle: { color: '#d8e2ef' } }
                }
            ],
            yAxis: [
                {
                    type: 'value',
                    axisLabel: { color: '#5e6e82' },
                    splitLine: { lineStyle: { color: '#d8e2ef', type: 'dashed' } }
                }
            ],
            series: [
                {
                    name: 'Pengunjung Unik',
                    type: 'line',
                    smooth: true, // Mengubah garis kaku menjadi kurva lembut
                    lineStyle: {
                        width: 2,
                        color: '#2ecc71' // Warna hijau fresh
                    },
                    showSymbol: false, // Menyembunyikan titik agar lebih bersih (muncul saat di-hover)
                    areaStyle: {
                        opacity: 0.3,
                        // Efek gradasi warna transparan di bawah garis
                        color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                            { offset: 0, color: '#2ecc71' }, 
                            { offset: 1, color: '#ffffff' }
                        ])
                    },
                    data: chartDataVisitor, // Data angka pengunjung
                    itemStyle: { color: '#2ecc71' }
                }
            ]
        };
        visitorChart.setOption(visitorOption);
    }

    // ==========================================
    // 3. AGAR GRAFIK RESPONSIVE SAAT RESIZE WINDOW
    // ==========================================
    window.addEventListener('resize', function() {
        if(donutChart) donutChart.resize();
        if(barChart) barChart.resize();
        if(visitorChart) visitorChart.resize();
    });

});
