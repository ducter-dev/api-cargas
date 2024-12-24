<!DOCTYPE html>
<html>
<head>
    <title>Archivos cargados en S3</title>
</head>
<body>
    <h1>Archivos cargados:</h1>

    @if (count($files) > 0)
        <ul>
            @foreach ($files as $file)
                <li>{{ $file }}</li>
            @endforeach
        </ul>
        <p>Total de archivos: {{ $total_files }}</p>
    @else
        <p>No se cargaron archivos.</p>
    @endif
</body>
</html>
