<?php
require_once '../includes/config.php';
require_once '../includes/verificar-gerente.php';

$titulo_pagina = 'Gestión de Ventas';
include('includes/header-gerente.php');
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="bg-light p-3 mb-4">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active" aria-current="page">Ventas</li>
    </ol>
</nav>

<!-- Contenido principal -->
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h2><i class="bi bi-graph-up"></i> Gestión de Ventas</h2>
            <p class="text-muted">Administra y visualiza las ventas de tu sucursal</p>
        </div>
    </div>

    <!-- Tarjetas de estadísticas rápidas -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small">Ventas Hoy</p>
                            <h4 class="mb-0 fw-bold">$0.00</h4>
                        </div>
                        <div class="bg-primary bg-opacity-10 rounded-circle p-3">
                            <i class="bi bi-cash-coin text-primary fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small">Ventas Mes</p>
                            <h4 class="mb-0 fw-bold">$0.00</h4>
                        </div>
                        <div class="bg-success bg-opacity-10 rounded-circle p-3">
                            <i class="bi bi-calendar-month text-success fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small">Total Transacciones</p>
                            <h4 class="mb-0 fw-bold">0</h4>
                        </div>
                        <div class="bg-info bg-opacity-10 rounded-circle p-3">
                            <i class="bi bi-receipt text-info fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small">Ticket Promedio</p>
                            <h4 class="mb-0 fw-bold">$0.00</h4>
                        </div>
                        <div class="bg-warning bg-opacity-10 rounded-circle p-3">
                            <i class="bi bi-calculator text-warning fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Card principal -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-table me-2"></i>Reportes de Ventas</h5>
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-funnel me-1"></i>Filtrar
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-success">
                        <i class="bi bi-file-earmark-excel me-1"></i>Exportar
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>
                <strong>Página en construcción</strong> - Esta funcionalidad se implementará próximamente.
            </div>

            <div class="text-center py-5">
                <i class="bi bi-graph-up-arrow text-muted" style="font-size: 4rem;"></i>
                <h5 class="text-muted mt-3">Sistema de Gestión de Ventas</h5>
                <p class="text-muted mb-4">Pronto podrás visualizar reportes detallados, gráficos de tendencias y análisis de ventas.</p>
                <div class="row justify-content-center">
                    <div class="col-md-8">
                        <div class="list-group">
                            <div class="list-group-item">
                                <i class="bi bi-check-circle text-success me-2"></i>
                                Reportes de ventas diarias, semanales y mensuales
                            </div>
                            <div class="list-group-item">
                                <i class="bi bi-check-circle text-success me-2"></i>
                                Gráficos de tendencias y comparativas
                            </div>
                            <div class="list-group-item">
                                <i class="bi bi-check-circle text-success me-2"></i>
                                Análisis por producto y categoría
                            </div>
                            <div class="list-group-item">
                                <i class="bi bi-check-circle text-success me-2"></i>
                                Exportación de datos en Excel y PDF
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('includes/footer-gerente.php'); ?>
