@if ($errors->any())
    <div class="validation-summary" role="alert" tabindex="-1" data-validation-summary>
        <strong>Periksa kembali isian Anda.</strong>
        <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif
