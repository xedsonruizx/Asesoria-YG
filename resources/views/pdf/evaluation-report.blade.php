<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Evaluación - {{ config('app.name') }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            background: #ffffff;
            color: #000000;
            line-height: 1.6;
            padding: 20px;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 10px;
            padding: 40px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border: 1px solid #e5e7eb;
        }
        
        .header {
            text-align: center;
            margin-bottom: 40px;
            border-bottom: 2px solid #f3f4f6;
            padding-bottom: 20px;
        }
        
        .company-logo {
            width: 120px;
            height: auto;
            margin-bottom: 15px;
        }
        
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 10px;
        }
        
        .report-title {
            font-size: 18px;
            color: #6b7280;
            margin-bottom: 20px;
        }
        
        .status-indicators {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin-bottom: 20px;
        }
        
        .status-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 8px;
        }
        
        .status-dot.attention { background-color: #ef4444; }
        .status-dot.improvable { background-color: #f59e0b; }
        .status-dot.excellent { background-color: #10b981; }
        
        .status-text {
            font-size: 14px;
            color: #4b5563;
        }
        
        .main-title {
            font-size: 28px;
            font-weight: bold;
            margin: 30px 0;
            text-align: center;
            color: #1f2937;
        }
        
        .categories-table {
            width: 100%;
            border-collapse: collapse;
            margin: 40px 0;
            background: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .categories-table th {
            background: #f8fafc;
            color: #1f2937;
            font-weight: bold;
            padding: 15px 12px;
            text-align: left;
            border-bottom: 2px solid #e5e7eb;
            font-size: 14px;
        }
        
        .categories-table td {
            padding: 12px;
            border-bottom: 1px solid #f1f5f9;
            color: #374151;
            font-size: 14px;
        }
        
        .categories-table tr:hover {
            background: #f8fafc;
        }
        
        .category-name {
            font-weight: 600;
            color: #1f2937;
        }
        
        .percentage-cell {
            font-weight: bold;
            font-size: 16px;
        }
        
        .percentage-cell.low { color: #ef4444; }
        .percentage-cell.medium { color: #f59e0b; }
        .percentage-cell.high { color: #10b981; }
        
        .status-cell {
            font-weight: 500;
        }
        
        .status-cell.poor { color: #ef4444; }
        .status-cell.fair { color: #f59e0b; }
        .status-cell.good { color: #10b981; }
        
        .description-cell {
            color: #6b7280;
            font-size: 13px;
            line-height: 1.4;
        }
        
        .stats-section {
            display: flex;
            justify-content: space-between;
            margin: 40px 0;
            gap: 20px;
        }
        
        .stat-card {
            background: #f9fafb;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            flex: 1;
            border: 1px solid #e5e7eb;
        }
        
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 8px;
        }
        
        .stat-label {
            font-size: 14px;
            color: #6b7280;
        }
        
        .actions-section {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin: 40px 0;
        }
        
        .action-button {
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            display: inline-block;
            border: 1px solid #d1d5db;
        }
        
        .action-button.secondary {
            background: #f9fafb;
            color: #374151;
        }
        
        .action-button.primary {
            background: #3b82f6;
            color: #ffffff;
            border-color: #3b82f6;
        }
        
        .disclaimer {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }
        
        .disclaimer-text {
            font-size: 12px;
            color: #6b7280;
            margin-bottom: 8px;
        }
        
        .contact-link {
            color: #3b82f6;
            text-decoration: underline;
        }
        
        /* Estilos para páginas separadas */
        .page-break {
            page-break-before: always;
        }
        
        .category-page {
            page-break-before: always;
            min-height: 100vh;
        }
        
        .category-page:first-child {
            page-break-before: auto;
        }
        
        .category-header {
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f3f4f6;
        }
        
        .category-title {
            font-size: 32px;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 20px;
        }
        
        .category-score {
            font-size: 48px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .category-score.low { color: #ef4444; }
        .category-score.medium { color: #f59e0b; }
        .category-score.high { color: #10b981; }
        
        .category-status {
            font-size: 20px;
            font-weight: 500;
            margin-bottom: 15px;
        }
        
        .category-description {
            font-size: 16px;
            color: #6b7280;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .questions-section {
            margin-top: 40px;
        }
        
        .questions-title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 30px;
            text-align: center;
            color: #1f2937;
        }
        
        .question-item {
            margin-bottom: 20px;
            background: #f9fafb;
            border-radius: 8px;
            padding: 20px;
            border: 1px solid #e5e7eb;
            page-break-inside: avoid;
            break-inside: avoid;
        }
        
        .question-text {
            font-weight: 500;
            margin-bottom: 12px;
            color: #374151;
            font-size: 16px;
            page-break-after: avoid;
        }
        
        .answer-box {
            background: #ffffff;
            border-radius: 6px;
            padding: 15px;
            color: #1f2937;
            font-size: 14px;
            border: 1px solid #d1d5db;
            page-break-before: avoid;
        }
        
        .question-category {
            margin-bottom: 30px;
            background: #f9fafb;
            border-radius: 12px;
            padding: 20px;
            border: 1px solid #e5e7eb;
            page-break-inside: avoid;
        }
        
        .question-category-title {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e5e7eb;
            color: #1f2937;
            page-break-after: avoid;
        }
        
        .company-info {
            position: fixed;
            bottom: 20px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 12px;
            color: #6b7280;
            background: #ffffff;
            border-top: 1px solid #e5e7eb;
            padding: 15px 20px;
            margin: 0;
            z-index: 1000;
        }
        
        /* Agregar margen inferior al contenido para evitar superposición con el footer */
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 10px;
            padding: 40px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border: 1px solid #e5e7eb;
            margin-bottom: 80px; /* Espacio para el footer fijo */
        }
        
        .category-page .container {
            margin-bottom: 80px; /* Espacio para el footer fijo en páginas de categoría */
        }
        
        /* Asegurar que el footer aparezca en cada página impresa */
        @media print {
            .company-info {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                background: #ffffff;
                border-top: 1px solid #e5e7eb;
                padding: 10px 20px;
                font-size: 10px;
            }
            
            .container {
                margin-bottom: 60px;
            }
            
            .category-page .container {
                margin-bottom: 60px;
            }
        }
    </style>
</head>
<body>
    <!-- Página de resumen general -->
    <div class="container">
        <!-- Header con Logo y Empresa -->
        <div class="header">
            @if(file_exists(public_path('images/logo.png')))
                <img src="{{ public_path('images/logo.png') }}" alt="Logo" class="company-logo">
            @endif
            
            <div class="company-name">{{ config('app.name', 'Asesoría YG') }}</div>
            <div class="report-title">Reporte de Evaluación Empresarial</div>
            
            <div class="status-indicators">
                <div>
                    <span class="status-dot attention"></span>
                    <span class="status-text">Requiere atención</span>
                </div>
                <div>
                    <span class="status-dot improvable"></span>
                    <span class="status-text">Mejorable</span>
                </div>
                <div>
                    <span class="status-dot excellent"></span>
                    <span class="status-text">Excelente</span>
                </div>
            </div>
        </div>
        
        <h1 class="main-title">Resumen General de Evaluación</h1>
        
        <!-- Categories Table Section - Solo mostrar categorías con puntaje > 0 -->
        @php
            $visibleCategories = collect($report['categories'])->filter(function($category) {
                return $category['percentage'] > 0;
            });
        @endphp
        
        @if($visibleCategories->count() > 0)
        <table class="categories-table">
            <thead>
                <tr>
                    <th>Categoría</th>
                    <th>Porcentaje</th>
                    <th>Estado</th>
                    <th>Descripción</th>
                </tr>
            </thead>
            <tbody>
                @foreach($visibleCategories as $category)
                <tr>
                    <td class="category-name">{{ $category['name'] }}</td>
                    <td class="percentage-cell {{ $category['percentage'] < 50 ? 'low' : ($category['percentage'] < 80 ? 'medium' : 'high') }}">
                        {{ round($category['percentage']) }}%
                    </td>
                    <td class="status-cell {{ strtolower($category['status']) }}">
                        @php
                            $statusTranslations = [
                                'poor' => 'Deficiente',
                                'fair' => 'Regular', 
                                'good' => 'Bueno',
                                'excellent' => 'Excelente'
                            ];
                            $translatedStatus = $statusTranslations[strtolower($category['status'])] ?? $category['status'];
                        @endphp
                        {{ $translatedStatus }}
                    </td>
                    <td class="description-cell">
                        @if($category['percentage'] < 50)
                            Hay aspectos críticos que requieren atención inmediata.
                        @elseif($category['percentage'] < 80)
                            Hay aspectos que podrían mejorarse.
                        @else
                            Excelente desempeño en esta área.
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <div style="text-align: center; padding: 40px; color: #6b7280;">
            <p>No hay categorías con puntaje para mostrar en este momento.</p>
        </div>
        @endif
        
        <!-- Stats Section - Solo mostrar estadísticas de categorías visibles -->
        {{-- <div class="stats-section">
            <div class="stat-card">
                <div class="stat-number">{{ array_sum(array_map('count', $questionsByCategory)) }}</div>
                <div class="stat-label">Preguntas respondidas</div>
            </div>
            
            @foreach($visibleCategories as $category)
            <div class="stat-card">
                <div class="stat-number">{{ round($category['percentage']) }}%</div>
                <div class="stat-label">Puntaje {{ $category['name'] }}</div>
            </div>
            @endforeach
        </div> --}}
        
 
        
        <!-- Disclaimer -->
        <div class="disclaimer">
            <div class="disclaimer-text">
                Esta evaluación es solo orientativa y no constituye asesoría legal profesional.
            </div>
            <div class="disclaimer-text">
                Para obtener asesoría personalizada, <a href="#" class="contact-link">contacte con nuestros especialistas</a>.
            </div>
        </div>
    </div>
    
    <!-- Páginas individuales por categoría - Solo para categorías con puntaje > 0 -->
    @foreach($questionsByCategory as $categoryName => $questions)
    @php
        $categoryData = collect($report['categories'])->firstWhere('name', $categoryName);
        $percentage = $categoryData ? $categoryData['percentage'] : 0;
        $status = $categoryData ? $categoryData['status'] : 'N/A';
    @endphp
    
    @if($percentage > 0)
    <div class="category-page">
        <div class="container">
            <!-- Header de la categoría -->
            <div class="category-header">
                <h1 class="category-title">{{ $categoryName }}</h1>
                <div class="category-score {{ $percentage < 50 ? 'low' : ($percentage < 80 ? 'medium' : 'high') }}">
                    {{ round($percentage) }}%
                </div>
                <div class="category-status {{ strtolower($status) }}">
                    @php
                        $statusTranslations = [
                            'poor' => 'Deficiente',
                            'fair' => 'Regular', 
                            'good' => 'Bueno',
                            'excellent' => 'Excelente'
                        ];
                        $translatedStatus = $statusTranslations[strtolower($status)] ?? $status;
                    @endphp
                    Estado: {{ $translatedStatus }}
                </div>
                <div class="category-description">
                    @if($percentage < 50)
                        Esta categoría presenta aspectos críticos que requieren atención inmediata. Se recomienda implementar mejoras urgentes para optimizar el rendimiento en esta área.
                    @elseif($percentage < 80)
                        Esta categoría muestra un rendimiento aceptable, pero hay aspectos que podrían mejorarse. Con algunas optimizaciones se puede alcanzar un nivel excelente.
                    @else
                        ¡Excelente desempeño en esta categoría! Los resultados demuestran un manejo óptimo de los aspectos evaluados en esta área.
                    @endif
                </div>
            </div>
            
            <!-- Preguntas y respuestas de la categoría -->
            <div class="questions-section">
                <h2 class="questions-title">Detalle de Preguntas y Respuestas</h2>
                
                @foreach($questions as $item)
                <div class="question-item">
                    <div class="question-text">{{ $item['question'] }}</div>
                    <div class="answer-box">
                        @if(is_array($item['answer']))
                            {{ implode(', ', $item['answer']) }}
                        @elseif(is_bool($item['answer']))
                            {{ $item['answer'] ? 'Sí' : 'No' }}
                        @else
                            {{ $item['answer'] }}
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
            
            <!-- Company Info en cada página -->
            <div class="company-info">
                <p>{{ config('app.name', 'Asesoría YG') }} - Categoría: {{ $categoryName }}</p>
                <p>Generado el {{ date('d/m/Y H:i') }}</p>
                <p style="margin-top: 10px; font-style: italic;">Este documento es confidencial y está destinado únicamente al uso del destinatario.</p>
            </div>
        </div>
    </div>
    @endif
    @endforeach
</body>
</html>