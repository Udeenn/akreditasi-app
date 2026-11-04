@props([
    'name' => 'info-circle',
    'class' => '',
    'size' => '1.15em',
])

{!! \App\Helpers\SvgHelper::render($name, $class, $size) !!}
