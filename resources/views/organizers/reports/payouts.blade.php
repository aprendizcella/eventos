@extends('layouts.app')

@section('content')
    <livewire:organizers.reports.payout-reports :organizer="$organizer" />
@endsection
