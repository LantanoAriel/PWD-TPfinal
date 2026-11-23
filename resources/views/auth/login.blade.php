@extends('layouts.app')

@section('titulo', 'Ingresar')

@section('contenido')
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <h1 class="h3 mb-3">Ingresar</h1>
            <p class="text-body-secondary">Para publicar y gestionar tus cocheras. Para buscar no hace falta cuenta.</p>

            <form method="POST" action="{{ route('login') }}" novalidate>
                @csrf

                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" id="email" name="email" autocomplete="email" autofocus required
                           class="form-control @error('email') is-invalid @enderror"
                           value="{{ old('email') }}">
                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Contraseña</label>
                    <input type="password" id="password" name="password" autocomplete="current-password" required
                           class="form-control @error('password') is-invalid @enderror">
                    @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="recordar" name="recordar" value="1">
                    <label class="form-check-label" for="recordar">Mantener la sesión iniciada</label>
                </div>

                <button type="submit" class="btn btn-primary">Ingresar</button>
                <a href="{{ route('register') }}" class="btn btn-link">Crear una cuenta</a>
            </form>
        </div>
    </div>
@endsection
