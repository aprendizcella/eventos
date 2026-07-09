@extends('layouts.app')

@section('content')
    <livewire:organizers.reports.billing-reports :organizer="$organizer" />
@endsection
