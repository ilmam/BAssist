<!--begin::Header-->
<div id="kt_header" class="header  align-items-stretch" 			data-kt-sticky="true"
		data-kt-sticky-name="header"
		data-kt-sticky-offset="{default: '200px', lg: '300px'}"
	>
	<!--begin::Container-->
	<div class="header-container  container-xxl  d-flex align-items-center">
		<!--begin::Heaeder menu toggle-->
		<div class="d-flex topbar align-items-center d-lg-none ms-n2 me-3" title="Show aside menu">
			<div class="btn btn-icon btn-color-gray-900 w-30px h-30px" id="kt_header_menu_mobile_toggle">
				<!--begin::Svg Icon | path: icons/duotune/abstract/abs015.svg-->
<span class="svg-icon svg-icon-2"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M21 7H3C2.4 7 2 6.6 2 6V4C2 3.4 2.4 3 3 3H21C21.6 3 22 3.4 22 4V6C22 6.6 21.6 7 21 7Z" fill="currentColor"/>
<path opacity="0.3" d="M21 14H3C2.4 14 2 13.6 2 13V11C2 10.4 2.4 10 3 10H21C21.6 10 22 10.4 22 11V13C22 13.6 21.6 14 21 14ZM22 20V18C22 17.4 21.6 17 21 17H3C2.4 17 2 17.4 2 18V20C2 20.6 2.4 21 3 21H21C21.6 21 22 20.6 22 20Z" fill="currentColor"/>
</svg>
</span>
<!--end::Svg Icon-->			</div>
		</div>
		<!--end::Heaeder menu toggle-->
		<!--begin::Header Logo-->
		<div class="header-logo me-5 me-md-10 flex-grow-1 flex-lg-grow-0">
			<a href="{{ url('/') }}">
				<img alt="Logo" src="{{ ui_asset('media/logos/default.png') }}" class="theme-light-show d-none d-lg-block h-20px" style="height:22px;width:auto;max-width:140px"/>
				<img alt="Logo" src="{{ ui_asset('media/logos/default-dark.png') }}" class="theme-dark-show d-none d-lg-block h-20px" style="height:22px;width:auto;max-width:140px"/>
				<img alt="Logo" src="{{ ui_asset('media/logos/default-small.png') }}" class="d-lg-none h-20px" style="height:22px;width:auto;max-width:140px"/>
			</a>
		</div>
		<!--end::Header Logo-->
		<!--begin::Wrapper-->
		<div class="d-flex align-items-stretch justify-content-end flex-lg-grow-1">
			<!--begin::Navbar-->
			<div class="d-flex align-items-stretch" id="kt_header_nav">
				@include('themes.metronic8.partials.header-menu')
			</div>
			<!--end::Navbar-->
			@include('themes.metronic8.partials.topbar')
		</div>
		<!--end::Wrapper-->
	</div>
	<!--end::Container-->
</div>
<!--end::Header-->