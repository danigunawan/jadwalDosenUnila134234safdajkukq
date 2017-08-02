@extends('layouts.app')

@section('content')
	<div class="container">
		<div class="row">
			<div class="col-md-12">
				<ul class="breadcrumb">
					<li><a href="{{ url('/home') }} ">Home</a></li>
					<li><a href="{{ url('/admin/master_mata_kuliahs') }}">Mata Kuliah</a></li>
					<li class="active">Edit Mata Kuliah</li>
				</ul>

				<div class="panel panel-default">
					<div class="panel-heading">
						<h2 class="panel-title">Edit Mata Kuliah</h2>
					</div>

					<div class="panel-body">
						{!! Form::model($master_mata_kuliahs, ['url' => route('master_mata_kuliahs.update', $master_mata_kuliahs->id), 'method' => 'put', 'files'=>'true','class'=>'form-horizontal']) !!}
						@include('master_mata_kuliahs._form')
						{!! Form::close() !!}
					</div>
				</div>
			</div>
		</div>
	</div>
@endsection
	