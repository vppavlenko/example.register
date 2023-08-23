<?php

namespace WS\Components;

use CFile;
use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use CIBlockElement;
use CPageOption;

use WS\Services\Storage;
use WS\Tools\Module;
use ZipArchive;

class ReportsList extends \CBitrixComponent
{
    const ACTION_REPORTS_REMOVE = 'remove';
    const ACTION_REPORTS_REMOVE_FILE = 'removeFile';
    const ACTION_REPORTS_DOWNLOAD = 'download';

    /** @var Storage $storage */
    private $storage;
    private $navString;

    public function executeComponent() {
        $this->includeModules();
        $this->SetOptionString();
        $this->initStorage();
        //$this->download();
        $this->clearStorage();
        $this->deactivate();
        $this->download();
        $this->removeFile();
        $this->initResult();
        $this->includeComponentTemplate();
    }

    private function includeModules() {
        Loader::includeModule('iblock');
    }

    private function SetOptionString() {
        CPageOption::SetOptionString('main', 'nav_page_in_session', 'N');
    }

    private function initResult() {
        $this->arResult = [
            'FILTER' => [
                'FORMAT' => $this->getPropertyFormatList(),
                'TYPE' => $this->getPropertyTypeList(),
            ],
            'ITEMS' => $this->getItems(),
            'CHECKED_ITEMS' => $this->getCheckedItems(),
            'REPORTS_FILTER' => $this->getFilter(),
            'NAV_STRING' => $this->navString,
        ];
    }

    private function getPropertyList($code) {
        $arItems = [];
        if (!empty($code)) {
            $rsProperty = \CIBlockPropertyEnum::GetList(
                ['VALUE' => 'ASC'],
                ['IBLOCK_ID' => $this->arParams['IBLOCK_ID'], 'CODE' => $code]
            );
            while ($arProperty = $rsProperty->GetNext()) {
                $arItems[$arProperty['ID']] = [
                    'ID' => $arProperty['ID'],
                    'VALUE' => $arProperty['VALUE'],
                ];
            }
        }
        return $arItems;
    }

    private function getPropertyTypeList() {
        return $this->getPropertyList('REPORT_TYPE');
    }

    private function getPropertyFormatList() {
        return $this->getPropertyList('DOCUMENT_TYPE');
    }

    private function getItems() {
        $arItems = [];
        $rsElement = \CIBlockElement::GetList(
            $this->getSort(),
            $this->getFilter(),
            false,
            $this->getNavParam(),
            $this->getSelect()
        );

        while ($arElement = $rsElement->Fetch()) {
            if (!empty($arElement['PROPERTY_REPORT_FILE_VALUE'])) {
                $arElement['REPORT_FILE'] = CFile::GetFileArray($arElement['PROPERTY_REPORT_FILE_VALUE']);
            }
            $arItems[] = $arElement;
        }
        $this->navString = $rsElement->GetPageNavString(
            false,
            'pagination',
            false,
            $this
        );

        return $arItems;

    }

    private function getCheckedItems() {
        return $this->storage->getStorageReports();
    }

    private function getSort() {
        return [
            $this->arParams['ELEMENT_SORT_FIELD'] => $this->arParams['ELEMENT_SORT_ORDER'],
        ];
    }

    private function getFilter() {
        $request = Application::getInstance()->getContext()->getRequest();

        $arItems = [
            'IBLOCK_ID' => $this->arParams['IBLOCK_ID'],
            'ACTIVE' => 'Y',
        ];

        $name = trim(strip_tags($request->getPost('name')));
        if (!empty($name)) {
            $arItems['NAME'] = '%' . $name . '%';
        }

        $type = $request->getPost('type');
        if (!empty($type)) {
            $arItems['PROPERTY_REPORT_TYPE'] = $type;
        }

        $format = $request->getPost('format');
        if (!empty($format)) {
            $arItems['PROPERTY_DOCUMENT_TYPE'] = $format;
        }

        return $arItems;
    }

    private function getNavParam() {
        return [
            'nPageSize' => $this->arParams['LIMIT'],
            'bShowAll' => false,
        ];
    }

    private function getSelect() {
        return [
            'ID',
            'NAME',
            'IBLOCK_ID',
            'DATE_CREATE',
            'PROPERTY_REPORT_TYPE',
            'PROPERTY_DOCUMENT_TYPE',
            'PROPERTY_DOCUMENT_SIZE',
            'PROPERTY_REPORT_FILE',
        ];
    }

    private function initStorage() {
        $this->storage = Module::getInstance()->getService('storage');
    }

    private function clearStorage() {
        if (!$this->request->isAjaxRequest()) {
            $this->storage->clearStorageReports();
        }
    }

    private function deactivate() {
        $request = Application::getInstance()->getContext()->getRequest();
        $action = $request->getPost('action');

        if (!$action || ($action != self::ACTION_REPORTS_REMOVE)) {
            return;
        }

        $removeReports = [];
        $reports = $this->storage->getStorageReports();
        $rsElement = \CIBlockElement::GetList(
            [],
            ['ID' => $reports, 'IBLOCK_ID' => IBLOCK_REPORTS_ID],
            false,
            false,
            ['ID', 'ACTIVE']
        );
        while ($arElement = $rsElement->Fetch()) {
            if ($arElement['ACTIVE'] == 'Y') {
                $el = new CIBlockElement;
                if ($el->Update($arElement['ID'], ['ACTIVE' => 'N' ])) {
                    $removeReports[] = $arElement['ID'];
                }
            }
        }
        $this->storage->removeStorageReports($removeReports);
    }

    private function download() {
        $request = Application::getInstance()->getContext()->getRequest();
        if (!$this->request->isAjaxRequest()) {
            return;
        }
        $action = $request->get('action');

        if (!$action || ($action != self::ACTION_REPORTS_DOWNLOAD)) {
            return;
        }

        $downloadReports = [];
        $downloadFiles = [];
        $reports = $this->storage->getStorageReports();

        if (count($reports) < 1) {
            return;
        }

        $rsElement = \CIBlockElement::GetList(
            [],
            ['ID' => $reports, 'IBLOCK_ID' => IBLOCK_REPORTS_ID],
            false,
            false,
            ['ID', 'IBLOCK_ID', 'PROPERTY_REPORT_FILE']
        );
        while ($arElement = $rsElement->Fetch()) {
            if (!empty($arElement['PROPERTY_REPORT_FILE_VALUE'])) {
                $downloadReports[] = $arElement['ID'];
                $downloadFiles[] = $arElement['PROPERTY_REPORT_FILE_VALUE'];
            }
        }
        $downloadFiles = array_unique($downloadFiles);
        $zipName = $zipDir = '';
        $result['success'] = false;
        if (count($downloadFiles) > 0) {
            $arFiles = [];
            foreach ($downloadFiles as $fileId) {
                $arFiles[] = CFile::GetFileArray($fileId);
            }

            global $USER;
            $userId = $USER->GetID();

            $zipRoot = $_SERVER['DOCUMENT_ROOT'];
            $zipDir = '/upload/reports/';
            $zipName = 'reports_u' . $userId . '_date_' . date('d_m_Y_H_i_s') . '.zip';

            $zip = new ZipArchive();
            $zip->open($zipRoot . $zipDir . $zipName , ZIPARCHIVE::CREATE);
            foreach ($arFiles as $index => $file){
                $zip->addFile($_SERVER['DOCUMENT_ROOT'].'/'.$file['SRC'], $file['ID'] . '_' . $file['FILE_NAME']);
            }
            $zip->close();

            $this->storage->removeStorageReports($downloadReports);
        }

        if (!empty($zipName)) {
            $result['success'] = true;
            $result['link'] = $zipDir . $zipName;
        }

        global $APPLICATION;
        $APPLICATION->RestartBuffer();
        echo json_encode($result);
        die();
    }

    private function removeFile() {
        $request = Application::getInstance()->getContext()->getRequest();
        if (!$this->request->isAjaxRequest()) {
            return;
        }
        $action = $request->get('action');
        $file = $request->get('file');

        if (!$action || ($action != self::ACTION_REPORTS_REMOVE_FILE)) {
            return;
        }

        $path = $_SERVER['DOCUMENT_ROOT'] . $file;

        if (!$file || !file_exists($path)) {
            return;
        }
        unlink($path);
        global $APPLICATION;
        $APPLICATION->RestartBuffer();
        echo json_encode(['success' => true]);
        die();
    }
}