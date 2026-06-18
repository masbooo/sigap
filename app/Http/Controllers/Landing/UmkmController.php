<?php

class UmkmController extends Controller
{
    public function index(?string $page = null)
    {
        $requestedPage = $page !== null && trim($page) !== ''
            ? (int) $page
            : (int) ($_GET['page'] ?? 1);

        if (!$this->isAjaxRequest() && isset($_GET['page'])) {
            $this->redirectToCanonicalPage($requestedPage);
            return;
        }

        $umkmModel = $this->model('Umkm');
        $filters = $_GET;

        $filters['page'] = max(1, $requestedPage);

        $pageData = $umkmModel->getPageData($filters);

        $this->view('landing.umkm', [
            'title' => 'SIGAP - UMKM',
            'umkmItems' => $pageData['items'],
            'umkmFilters' => $pageData['filters'],
            'umkmFilterOptions' => $pageData['filterOptions'],
            'umkmAllItemsCount' => $pageData['allItemsCount'],
            'umkmFilteredItemsCount' => $pageData['filteredItemsCount'],
            'umkmPagination' => $pageData['pagination'],
        ]);
    }

    private function isAjaxRequest(): bool
    {
        $requestedWith = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''));
        $umkmSection = strtolower((string) ($_SERVER['HTTP_X_UMKM_SECTION'] ?? ''));
        $pjax = strtolower((string) ($_SERVER['HTTP_X_PJAX'] ?? ''));

        return $requestedWith === 'xmlhttprequest' || $umkmSection === 'true' || $pjax === 'true';
    }

    private function redirectToCanonicalPage(int $page): void
    {
        $query = $_GET;
        unset($query['page']);

        $target = $page > 1
            ? base_url('umkm/' . $page)
            : base_url('umkm');

        if (!empty($query)) {
            $target .= '?' . http_build_query($query);
        }

        header('Location: ' . $target);
        exit;
    }
}
