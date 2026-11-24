@php($themeVariables = \App\Services\ThemeSettings::cssVariables())
<style>
    :root {
        @foreach ($themeVariables as $name => $value)
            {{ $name }}: {{ $value }};
        @endforeach
    }
</style>
