<?php
require_once '../includes/config.php';
require_once '../includes/verificar-gerente.php';

$titulo_pagina = 'Gestión de Stock';
include('includes/header-gerente.php');
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="bg-light p-3 mb-4">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active" aria-current="page">Stock</li>
    </ol>
</nav>

<!-- Contenido principal -->
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h2><i class="bi bi-boxes"></i> Gestión de Stock</h2>
            <p class="text-muted">Controla el inventario y existencias de tu sucursal</p>
        </div>
    </div>

    <!-- Tarjetas de estadísticas rápidas -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small">Total Productos</p>
                            <h4 class="mb-0 fw-bold">0</h4>
                        </div>
                        <div class="bg-primary bg-opacity-10 rounded-circle p-3">
                            <i class="bi bi-box-seam text-primary fs-4"></i>
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
                            <p class="text-muted mb-1 small">Stock Bajo</p>
                            <h4 class="mb-0 fw-bold text-warning">0</h4>
                        </div>
                        <div class="bg-warning bg-opacity-10 rounded-circle p-3">
                            <i class="bi bi-exclamation-triangle text-warning fs-4"></i>
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
                            <p class="text-muted mb-1 small">Sin Stock</p>
                            <h4 class="mb-0 fw-bold text-danger">0</h4>
                        </div>
                        <div class="bg-danger bg-opacity-10 rounded-circle p-3">
                            <i class="bi bi-x-circle text-danger fs-4"></i>
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
                            <p class="text-muted mb-1 small">Stock Óptimo</p>
                            <h4 class="mb-0 fw-bold text-success">0</h4>
                        </div>
                        <div class="bg-success bg-opacity-10 rounded-circle p-3">
                            <i class="bi bi-check-circle text-success fs-4"></i>
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
                <h5 class="mb-0"><i class="bi bi-grid me-2"></i>Inventario de Productos</h5>
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-search me-1"></i>Buscar
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-funnel me-1"></i>Filtrar
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-success">
                        <i class="bi bi-plus-lg me-1"></i>Solicitar Stock
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
                <i class="bi bi-boxes text-muted" style="font-size: 4rem;"></i>
                <h5 class="text-muted mt-3">Sistema de Gestión de Inventario</h5>
                <p class="text-muted mb-4">Pronto podrás gestionar el stock de tu sucursal de manera eficiente.</p>
                <div class="row justify-content-center">
                    <div class="col-md-8">
                        <div class="list-group">
                            <div class="list-group-item">
                                <i class="bi bi-check-circle text-success me-2"></i>
                                Visualización en tiempo real del inventario
                            </div>
                            <div class="list-group-item">
                                <i class="bi bi-check-circle text-success me-2"></i>
                                Alertas de stock bajo y productos agotados
                            </div>
                            <div class="list-group-item">
                                <i class="bi bi-check-circle text-success me-2"></i>
                                Solicitudes de reabastecimiento
                            </div>
                            <div class="list-group-item">
                                <i class="bi bi-check-circle text-success me-2"></i>
                                Historial de movimientos de inventario
                            </div>
                            <div class="list-group-item">
                                <i class="bi bi-check-circle text-success me-2"></i>
                                Reportes y análisis de rotación de productos
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('includes/footer-gerente.php'); ?>
