/**
 * GRAFICOS.JS - GRÁFICOS PARA DASHBOARDS
 *
 * Utiliza Chart.js para mostrar:
 * - Gráficos de líneas (ventas mensuales)
 * - Gráficos de dona (productos por categoría)
 * - Gráficos de barras (estadísticas)
 *
 * Dependencias: Chart.js 3.x
 */

'use strict';

// =============================================================================
// CONFIGURACIÓN GLOBAL DE CHART.JS
// =============================================================================

const ChartConfig = {
    defaultColors: {
        primary: '#3C50E0',
        success: '#10B981',
        warning: '#F59E0B',
        danger: '#EF4444',
        info: '#3B82F6',
        purple: '#8B5CF6',
        pink: '#EC4899',
        teal: '#14B8A6'
    },

    defaultOptions: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                display: true,
                position: 'top',
                labels: {
                    padding: 15,
                    font: {
                        size: 12,
                        family: "'Inter', sans-serif"
                    }
                }
            },
            tooltip: {
                backgroundColor: '#1C2434',
                padding: 12,
                titleFont: {
                    size: 14,
                    weight: 'bold'
                },
                bodyFont: {
                    size: 13
                },
                cornerRadius: 8,
                displayColors: true
            }
        }
    }
};

// =============================================================================
// GRÁFICO DE VENTAS MENSUALES (LÍNEAS)
// =============================================================================

const GraficoVentas = {

    /**
     * Crear gráfico de ventas mensuales
     * @param {string} canvasId - ID del elemento canvas
     * @param {Array} labels - Etiquetas de meses
     * @param {Array} datos - Datos de ventas
     */
    crear(canvasId, labels, datos) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) {
            console.error(`Canvas con ID "${canvasId}" no encontrado`);
            return null;
        }

        const ctx = canvas.getContext('2d');

        return new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Ventas ($)',
                    data: datos,
                    borderColor: ChartConfig.defaultColors.primary,
                    backgroundColor: this.hexToRgba(ChartConfig.defaultColors.primary, 0.1),
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    pointBackgroundColor: ChartConfig.defaultColors.primary,
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointHoverBackgroundColor: ChartConfig.defaultColors.primary,
                    pointHoverBorderColor: '#fff'
                }]
            },
            options: {
                ...ChartConfig.defaultOptions,
                plugins: {
                    ...ChartConfig.defaultOptions.plugins,
                    legend: {
                        display: false
                    },
                    tooltip: {
                        ...ChartConfig.defaultOptions.plugins.tooltip,
                        callbacks: {
                            label: (context) => {
                                return 'Ventas: $' + context.parsed.y.toLocaleString('es-AR', {
                                    minimumFractionDigits: 2,
                                    maximumFractionDigits: 2
                                });
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            borderDash: [5, 5],
                            color: 'rgba(0, 0, 0, 0.05)'
                        },
                        ticks: {
                            callback: function (value) {
                                return '$' + value.toLocaleString('es-AR');
                            },
                            font: {
                                size: 11
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 11
                            }
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                }
            }
        });
    },

    /**
     * Convertir hex a rgba
     */
    hexToRgba(hex, alpha = 1) {
        const r = parseInt(hex.slice(1, 3), 16);
        const g = parseInt(hex.slice(3, 5), 16);
        const b = parseInt(hex.slice(5, 7), 16);
        return `rgba(${r}, ${g}, ${b}, ${alpha})`;
    }
};

// =============================================================================
// GRÁFICO DE PRODUCTOS POR CATEGORÍA (DONA)
// =============================================================================

const GraficoCategorias = {

    /**
     * Crear gráfico de dona
     * @param {string} canvasId - ID del elemento canvas
     * @param {Array} labels - Nombres de categorías
     * @param {Array} datos - Cantidad de productos
     * @param {Array} colores - Colores personalizados (opcional)
     */
    crear(canvasId, labels, datos, colores = null) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) {
            console.error(`Canvas con ID "${canvasId}" no encontrado`);
            return null;
        }

        const ctx = canvas.getContext('2d');

        // Usar colores predeterminados si no se proporcionan
        const backgroundColors = colores || [
            ChartConfig.defaultColors.primary,
            ChartConfig.defaultColors.success,
            ChartConfig.defaultColors.warning,
            ChartConfig.defaultColors.danger,
            ChartConfig.defaultColors.purple,
            ChartConfig.defaultColors.pink,
            ChartConfig.defaultColors.teal
        ];

        return new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: datos,
                    backgroundColor: backgroundColors,
                    borderWidth: 0,
                    hoverOffset: 10
                }]
            },
            options: {
                ...ChartConfig.defaultOptions,
                plugins: {
                    ...ChartConfig.defaultOptions.plugins,
                    legend: {
                        display: false
                    },
                    tooltip: {
                        ...ChartConfig.defaultOptions.plugins.tooltip,
                        callbacks: {
                            label: (context) => {
                                const label = context.label || '';
                                const value = context.parsed || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((value / total) * 100).toFixed(1);
                                return `${label}: ${value} productos (${percentage}%)`;
                            }
                        }
                    }
                },
                cutout: '70%'
            }
        });
    }
};

// =============================================================================
// GRÁFICO DE BARRAS (COMPARATIVAS)
// =============================================================================

const GraficoBarras = {

    /**
     * Crear gráfico de barras
     * @param {string} canvasId - ID del elemento canvas
     * @param {Array} labels - Etiquetas
     * @param {Array} datasets - Conjuntos de datos
     */
    crear(canvasId, labels, datasets) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) {
            console.error(`Canvas con ID "${canvasId}" no encontrado`);
            return null;
        }

        const ctx = canvas.getContext('2d');

        return new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: datasets.map((dataset, index) => ({
                    label: dataset.label,
                    data: dataset.data,
                    backgroundColor: dataset.color || Object.values(ChartConfig.defaultColors)[index],
                    borderRadius: 8,
                    borderSkipped: false
                }))
            },
            options: {
                ...ChartConfig.defaultOptions,
                plugins: {
                    ...ChartConfig.defaultOptions.plugins,
                    tooltip: {
                        ...ChartConfig.defaultOptions.plugins.tooltip,
                        callbacks: {
                            label: (context) => {
                                return context.dataset.label + ': ' + context.parsed.y.toLocaleString('es-AR');
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            borderDash: [5, 5],
                            color: 'rgba(0, 0, 0, 0.05)'
                        },
                        ticks: {
                            font: {
                                size: 11
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 11
                            }
                        }
                    }
                }
            }
        });
    }
};

// =============================================================================
// UTILIDADES
// =============================================================================

const GraficosUtils = {

    /**
     * Destruir gráfico si existe
     */
    destruir(chart) {
        if (chart && typeof chart.destroy === 'function') {
            chart.destroy();
        }
    },

    /**
     * Actualizar datos de un gráfico existente
     */
    actualizarDatos(chart, nuevosLabels, nuevosDatos) {
        if (!chart) return;

        chart.data.labels = nuevosLabels;
        chart.data.datasets[0].data = nuevosDatos;
        chart.update();
    },

    /**
     * Crear gráfico genérico desde datos del servidor
     */
    async crearDesdeAPI(canvasId, url, tipo = 'line') {
        try {
            const response = await fetch(url);
            const data = await response.json();

            if (!data.success) {
                throw new Error(data.mensaje || 'Error al cargar datos del gráfico');
            }

            switch (tipo) {
                case 'line':
                    return GraficoVentas.crear(canvasId, data.labels, data.datos);
                case 'doughnut':
                    return GraficoCategorias.crear(canvasId, data.labels, data.datos, data.colores);
                case 'bar':
                    return GraficoBarras.crear(canvasId, data.labels, data.datasets);
                default:
                    throw new Error(`Tipo de gráfico "${tipo}" no soportado`);
            }

        } catch (error) {
            console.error('Error al crear gráfico:', error);
            return null;
        }
    }
};

// =============================================================================
// EXPORTAR FUNCIONES GLOBALES
// =============================================================================

window.GraficoVentas = GraficoVentas;
window.GraficoCategorias = GraficoCategorias;
window.GraficoBarras = GraficoBarras;
window.GraficosUtils = GraficosUtils;
window.ChartConfig = ChartConfig;
