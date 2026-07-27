<?php

namespace App\Http\Controllers\Landing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Exceptions\HttpResponseException;

class UmkmController extends Controller
{
    public function index(?string $page = null)
    {
        $requestedPage = $page !== null && trim($page) !== ''
            ? (int) $page
            : (int) request()->query('page', 1);

        if (!$this->isAjaxRequest() && request()->query->has('page')) {
            $this->redirectToCanonicalPage($requestedPage);
            return;
        }

        $umkmModel = $this->model('Umkm');
        $filters = request()->query();

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
        $requestedWith = strtolower((string) request()->header('X-Requested-With', ''));
        $umkmSection = strtolower((string) request()->header('X-UMKM-Section', ''));
        $pjax = strtolower((string) request()->header('X-PJAX', ''));

        return $requestedWith === 'xmlhttprequest' || $umkmSection === 'true' || $pjax === 'true';
    }

    private function redirectToCanonicalPage(int $page): void
    {
        $query = request()->query();
        unset($query['page']);

        $target = $page > 1
            ? url('umkm/' . $page)
            : url('umkm');

        if (!empty($query)) {
            $target .= '?' . http_build_query($query);
        }

        throw new HttpResponseException(redirect($target));
    }
}
