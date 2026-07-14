@props(['viewModel' => null])
@php
    /** @var \App\ViewModels\Public\EventSeoViewModel|null $viewModel */
@endphp

@if($viewModel)
    <title>{{ $viewModel->title() }}</title>
    <meta name="description" content="{{ $viewModel->description() }}">

    <link rel="canonical" href="{{ $viewModel->canonicalUrl() }}">

    {{-- Open Graph --}}
    <meta property="og:title" content="{{ $viewModel->ogMeta()['title'] }}">
    <meta property="og:description" content="{{ $viewModel->ogMeta()['description'] }}">
    <meta property="og:url" content="{{ $viewModel->ogMeta()['url'] }}">
    <meta property="og:type" content="{{ $viewModel->ogMeta()['type'] }}">
    <meta property="og:site_name" content="{{ $viewModel->ogMeta()['site_name'] }}">

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="{{ $viewModel->twitterMeta()['card'] }}">
    <meta name="twitter:title" content="{{ $viewModel->twitterMeta()['title'] }}">
    <meta name="twitter:description" content="{{ $viewModel->twitterMeta()['description'] }}">
@endif
