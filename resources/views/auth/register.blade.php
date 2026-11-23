@extends('layouts.app')

@section('titulo', 'Crear cuenta')

@section('contenido')
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <h1 class="h3 mb-3">Crear cuenta de vendedor</h1>

            <form method="POST" action="{{ route('register') }}" novalidate>
                @csrf

                <div class="mb-3">
                    <label for="name" class="form-label">Nombre</label>
                    <input type="text" id="name" name="name" maxlength="80" autocomplete="name" autofocus required
                           class="form-control @error('name') is-invalid @enderror"
                           value="{{ old('name') }}">
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" id="email" name="email" maxlength="120" autocomplete="email" required
                           class="form-control @error('email') is-invalid @enderror"
                           value="{{ old('email') }}">
                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Contraseña (mínimo 8 caracteres)</label>
                    <input type="password" id="password" name="password" autocomplete="new-password" required
                           class="form-control @error('password') is-invalid @enderror">
                    @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label for="password_confirmation" class="form-label">Repetir contraseña</label>
                    <input type="password" id="password_confirmation" name="password_confirmation"
                           autocomplete="new-password" required class="form-control">
                </div>

                <button type="submit" class="btn btn-primary">Crear cuenta</button>
                <a href="{{ route('login') }}" class="btn btn-link">Ya tengo cuenta</a>
            </form>
        </div>
    </div>
@endsection
