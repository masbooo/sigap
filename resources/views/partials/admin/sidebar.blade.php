@php
    $sidebarAdmin = $admin ?? admin_user() ?? [];
    $roleContext = resolve_admin_role_context($sidebarAdmin);
    $sidebarSections = $roleContext['sidebar_sections'] ?? [];
@endphp

<aside class="left-sidebar with-vertical">
    <div>
        <div class="brand-logo d-flex align-items-center justify-content-between">
            <a href="{{ base_url('admin/dasbor') }}" class="text-nowrap logo-img">
                <img src="{{ base_url('assets/custom/images/logos/logotxt_sigap_b.svg') }}" class="dark-logo" style="width:160px;" alt="Logo SIGAP" />
                <img src="{{ base_url('assets/custom/images/logos/logotxt_sigap_w.svg') }}" class="light-logo" alt="Logo SIGAP" />
            </a>
        </div>

        <nav class="sidebar-nav scroll-sidebar" data-simplebar data-simplebar-auto-hide="false">
            <ul id="sidebarnav" data-sigap-sidebar-mode="bootstrap-collapse">
                @foreach ($sidebarSections as $section)
                    <li class="nav-small-cap">
                        <i class="ti ti-dots nav-small-cap-icon fs-4"></i>
                        <span class="hide-menu">{{ $section['heading'] ?? 'MENU' }}</span>
                    </li>

                    @foreach ((array) ($section['items'] ?? []) as $item)
                        @php
                            $children = (array) ($item['children'] ?? []);
                            $hasChildren = !empty($children);
                            $collapseId = 'admin-menu-' . strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', (string) ($item['key'] ?? uniqid('menu-', false))), '-'));
                        @endphp

                        <li class="sidebar-item">
                            @if ($hasChildren)
                                <a
                                    class="sidebar-link has-arrow collapsed"
                                    href="#{{ $collapseId }}"
                                    data-bs-toggle="collapse"
                                    aria-expanded="false"
                                    aria-controls="{{ $collapseId }}"
                                >
                                    <i class="{{ $item['icon'] ?? 'ti ti-layout-grid' }}"></i>
                                    <span class="hide-menu">{{ $item['label'] ?? 'Menu' }}</span>
                                </a>

                                <ul id="{{ $collapseId }}" class="collapse first-level">
                                    @foreach ($children as $child)
                                        @php
                                            $grandChildren = (array) ($child['children'] ?? []);
                                            $hasGrandChildren = !empty($grandChildren);
                                            $childCollapseId = 'admin-menu-' . strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', (string) ($child['key'] ?? uniqid('child-', false))), '-'));
                                        @endphp

                                        <li class="sidebar-item">
                                            @if ($hasGrandChildren)
                                                <a
                                                    class="sidebar-link has-arrow collapsed"
                                                    href="#{{ $childCollapseId }}"
                                                    data-bs-toggle="collapse"
                                                    aria-expanded="false"
                                                    aria-controls="{{ $childCollapseId }}"
                                                >
                                                    <i class="{{ $child['icon'] ?? 'ti ti-circle' }}"></i>
                                                    <span class="hide-menu">{{ $child['label'] ?? 'Menu' }}</span>
                                                </a>

                                                <ul id="{{ $childCollapseId }}" class="collapse two-level">
                                                    @foreach ($grandChildren as $grandChild)
                                                        <li class="sidebar-item">
                                                            <a
                                                                class="sidebar-link{{ !empty($grandChild['is_ajax']) ? ' ajax-link' : '' }}"
                                                                href="{{ $grandChild['href'] ?? 'javascript:void(0)' }}"
                                                            >
                                                                <i class="{{ $grandChild['icon'] ?? 'ti ti-circle' }}"></i>
                                                                <span class="hide-menu">{{ $grandChild['label'] ?? 'Menu' }}</span>
                                                            </a>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            @else
                                                <a
                                                    class="sidebar-link{{ !empty($child['is_ajax']) ? ' ajax-link' : '' }}"
                                                    href="{{ $child['href'] ?? 'javascript:void(0)' }}"
                                                >
                                                    <i class="{{ $child['icon'] ?? 'ti ti-circle' }}"></i>
                                                    <span class="hide-menu">{{ $child['label'] ?? 'Menu' }}</span>
                                                </a>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <a
                                    class="sidebar-link{{ !empty($item['is_ajax']) ? ' ajax-link' : '' }}"
                                    href="{{ $item['href'] ?? 'javascript:void(0)' }}"
                                >
                                    <i class="{{ $item['icon'] ?? 'ti ti-layout-grid' }}"></i>
                                    <span class="hide-menu">{{ $item['label'] ?? 'Menu' }}</span>
                                </a>
                            @endif
                        </li>
                    @endforeach
                @endforeach
            </ul>
        </nav>
    </div>
</aside>
