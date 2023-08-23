<?php

namespace WS\Components;

use Bitrix\Main\Diag\Debug;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use CIBlockElement;
use CIBlockProperty;
use CIBlockPropertyEnum;
use CPageOption;
use Exception;

use WS\Entities\Patient;
use WS\Entities\RequestActions;
use WS\Entities\Requests;
use WS\Entities\User;
use WS\Tools\Module;
use WS\Tools\ORM\BitrixEntity\EnumElement;

class NoticesList extends \CBitrixComponent
{
    const ACTION_REQUEST_CONFIRM = 'confirm';
    const ACTION_REQUEST_CANCEL = 'cancel';

    const ROLE_CODE_ADMIN = 'administrator';
    const ROLE_CODE_MANAGER = 'manager';

    private $role;
    private $navString;

    private $userId;
    /** @var User $user */
    private $user;

    public function executeComponent() {
        $this->includeModules();
        $this->SetOptionString();
        $this->initUser();
        $this->initRole();
        $this->action();
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
            'ITEMS' => $this->getItems(),
            'TEXT' => $this->getText(),
            'NAV_STRING' => $this->navString,
            'NAV_LINK' => $this->getNavLink(),
            'USER_ACCESS' => $this->user->role,
        ];
    }

    private function getItems() {
        $props['USER'] = $this->getProperties(IBLOCK_USERS_ID);
        $props['PATIENT'] = $this->getProperties(IBLOCK_PATIENTS_ID);

        $userStatusMap = [
            'active' => 'Активный',
            'locked' => 'Заблокирован'
        ];
        $patientStatusMap = [
            'Y' => 'Активный',
            'N' => 'Удален'
        ];
        $roleMap = $this->getRoles();

        $arItems = [];
        $rsElement = \CIBlockElement::GetList(
            $this->getSort(),
            $this->getFilter(),
            false,
            $this->getNavParam(),
            $this->getSelect()
        );

        while ($obElement = $rsElement->GetNextElement()) {
            $arElement = $obElement->GetFields();
            $arElement['PROPERTIES'] = $obElement->GetProperties();
            $fieldCode = $arElement['PROPERTIES']['FIELD_CODE']['VALUE'];
            $arElement['CHANGE_FIELD_NAME'] = '';
            if ($arElement['PROPERTIES']['OBJECT_TYPE']['VALUE_XML_ID'] == 'user') {
                $arElement['CHANGE_FIELD_NAME'] = $props['USER'][$fieldCode]['NAME'];
                $arElement['REQUEST_NAME'] = $arElement['PROPERTY_USER_PROPERTY_LAST_NAME_VALUE'] . ' ' . $arElement['PROPERTY_USER_PROPERTY_FIRST_NAME_VALUE'] . ' ' . $arElement['PROPERTY_USER_PROPERTY_SECOND_NAME_VALUE'];
                if ($fieldCode == 'STATUS') {
                    $arElement['PROPERTIES']['OLD_VALUE']['VALUE'] = $userStatusMap[$arElement['PROPERTIES']['OLD_VALUE']['VALUE']];
                    $arElement['PROPERTIES']['NEW_VALUE']['VALUE'] = $userStatusMap[$arElement['PROPERTIES']['NEW_VALUE']['VALUE']];
                } else {
                    $propType = $props['USER'][$fieldCode]['PROPERTY_TYPE'];
                    if ($fieldCode == 'ROLE') {
                        $arElement['PROPERTIES']['OLD_VALUE']['VALUE'] = $roleMap[$arElement['PROPERTIES']['OLD_VALUE']['VALUE']];
                        $arElement['PROPERTIES']['NEW_VALUE']['VALUE'] = $roleMap[$arElement['PROPERTIES']['NEW_VALUE']['VALUE']];
                    }
                    if ($propType == 'L') {
                        $arElement['PROPERTIES']['OLD_VALUE']['VALUE'] = $props['USER'][$fieldCode]['ENUMS'][$arElement['PROPERTIES']['OLD_VALUE']['VALUE']]['VALUE'];
                        $arElement['PROPERTIES']['NEW_VALUE']['VALUE'] = $props['USER'][$fieldCode]['ENUMS'][$arElement['PROPERTIES']['NEW_VALUE']['VALUE']]['VALUE'];
                    }
                }

            }
            if ($arElement['PROPERTIES']['OBJECT_TYPE']['VALUE_XML_ID'] == 'patient') {
                $arElement['REQUEST_NAME'] = $arElement['PROPERTY_PATIENT_PROPERTY_P_LAST_NAME_VALUE'] . ' ' . $arElement['PROPERTY_PATIENT_PROPERTY_P_NAME_VALUE'] . ' ' . $arElement['PROPERTY_PATIENT_PROPERTY_P_SECOND_NAME_VALUE'];
                if ($fieldCode == 'ACTIVE') {
                    $arElement['CHANGE_FIELD_NAME'] = 'Статус';
                    $arElement['PROPERTIES']['OLD_VALUE']['VALUE'] = $patientStatusMap[$arElement['PROPERTIES']['OLD_VALUE']['VALUE']];
                    $arElement['PROPERTIES']['NEW_VALUE']['VALUE'] = $patientStatusMap[$arElement['PROPERTIES']['NEW_VALUE']['VALUE']];
                } else {
                    $arElement['CHANGE_FIELD_NAME'] = $props['PATIENT'][$fieldCode]['NAME'];
                    $propType = $props['PATIENT'][$arElement['PROPERTIES']['FIELD_CODE']['VALUE']]['PROPERTY_TYPE'];
                    if ($propType == 'L') {
                        $arElement['PROPERTIES']['OLD_VALUE']['VALUE'] = $props['PATIENT'][$arElement['PROPERTIES']['FIELD_CODE']['VALUE']]['ENUMS'][$arElement['PROPERTIES']['OLD_VALUE']['VALUE']]['VALUE'];
                        $arElement['PROPERTIES']['NEW_VALUE']['VALUE'] = $props['PATIENT'][$arElement['PROPERTIES']['FIELD_CODE']['VALUE']]['ENUMS'][$arElement['PROPERTIES']['NEW_VALUE']['VALUE']]['VALUE'];
                    }
                }
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

    private function getProperties($iblockId) {
        $props = [];
        $rsProp = CIBlockProperty::GetList([], array("ACTIVE" => "Y", "IBLOCK_ID" => $iblockId));
        while ($arr = $rsProp->Fetch()) {
            if ($arr['PROPERTY_TYPE'] == 'L') {
                $obEnums = CIBlockPropertyEnum::GetList([], ["IBLOCK_ID" => $iblockId, "PROPERTY_ID" => $arr['ID']]);
                while($arEnum = $obEnums->GetNext()) {
                    $arr['ENUMS'][$arEnum['ID']] = $arEnum;
                }
            }
            $props[$arr["CODE"]] = $arr;
        }
        return $props;
    }

    private function getSort() {
        return [
            $this->arParams['ELEMENT_SORT_FIELD'] => $this->arParams['ELEMENT_SORT_ORDER'],
        ];
    }

    private function getFilter() {
        $requestGw = $this->ormManager()->getGateway(Requests::class);
        $objectType = '';

        $arItems = [
            'IBLOCK_ID' => $this->arParams['IBLOCK_ID'],
            '!CREATED_BY' => $this->userId,
        ];

        if ($this->role == self::ROLE_CODE_ADMIN) {
            $objectType = Requests::OBJECT_TYPE_USER;
        }
        if ($this->role == self::ROLE_CODE_MANAGER) {
            $objectType = Requests::OBJECT_TYPE_PATIENT;
        }
        if (!empty($objectType)) {
            $arItems['PROPERTY_OBJECT_TYPE'] = $requestGw->getEnumVariant('objectType', $objectType)->id;
        }

        $arItems['PROPERTY_STATUS'] = $requestGw->getEnumVariant('status', Requests::STATUS_NEW)->id;

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
            'PROPERTY_USER.PROPERTY_LAST_NAME',
            'PROPERTY_USER.PROPERTY_FIRST_NAME',
            'PROPERTY_USER.PROPERTY_SECOND_NAME',
            'PROPERTY_PATIENT.PROPERTY_P_LAST_NAME',
            'PROPERTY_PATIENT.PROPERTY_P_NAME',
            'PROPERTY_PATIENT.PROPERTY_P_SECOND_NAME',
        ];
    }

    private function initUser() {
        global $USER;
        $this->userId = $USER->GetID();
        $this->user = $this->getUserEntity();
    }

    private function initRole() {
        if ($this->user->role->code == self::ROLE_CODE_ADMIN) {
            $this->role = self::ROLE_CODE_ADMIN;
        }
        if ($this->user->role->code == self::ROLE_CODE_MANAGER) {
            $this->role = self::ROLE_CODE_MANAGER;
        }
    }

    private function getUserEntity() {
        return $this->ormManager()
            ->createSelectRequest(User::class)
            ->equal('user', $this->userId)
            ->equal('active', 'Y')
            ->withRelations(['role'])
            ->getOne();
    }

    private function action() {
        $request = Application::getInstance()->getContext()->getRequest();
        if (!$this->request->isAjaxRequest()) {
            return;
        }
        $action = $request->getPost('action');

        $result = true;
        if ($action == self::ACTION_REQUEST_CONFIRM) {
            $result = $this->confirmRequest();
        }
        if ($action == self::ACTION_REQUEST_CANCEL) {
            $result = $this->cancelRequest();
        }

        if (!$result) {
            global $APPLICATION;
            $APPLICATION->RestartBuffer();
            echo json_encode(['error' => true]);
            die();
        }
    }

    private function confirmRequest() {
        $orm = $this->ormManager();
        $request = Application::getInstance()->getContext()->getRequest();
        $id = $request->get('id');

        /** @var Requests $request */
        $request = $orm->getById(Requests::class, $id);

        $propertyObjectTypeList = $this->getProperties(IBLOCK_REQUESTS_ID);
        $propertyObjectTypeList = $propertyObjectTypeList['OBJECT_TYPE']['ENUMS'];

        if ($propertyObjectTypeList[$request->objectType->id]['XML_ID'] == 'user') {
            $elementId = $request->user->id;
            $iblockId = IBLOCK_USERS_ID;
        }
        if ($propertyObjectTypeList[$request->objectType->id]['XML_ID'] == 'patient') {
            $elementId = $request->patient->id;
            $iblockId = IBLOCK_PATIENTS_ID;
        }

        if (!isset($elementId) || !isset($iblockId)) {
            return false;
        }

        $value = $request->newValue;
        $field = $request->field;

        if ($field == 'ACTIVE') {
            $el = new CIBlockElement;
            $el->Update(
                $elementId,
                ['ACTIVE' => $value]
            );
        } elseif ($field == 'STATUS') {
            $userGw = $orm->getGateway(User::class);
            $value = $userGw->getEnumVariant('status', $value)->id;
            CIBlockElement::SetPropertyValuesEx($elementId, $iblockId, array($field => $value));
        }
        else {
            CIBlockElement::SetPropertyValuesEx($elementId, $iblockId, array($field => $value));
        }

        $requestAction = $this->createRequestActions($request->name, 'confirm');
        if (!$requestAction) {
            return false;
        }

        $requestGw = $orm->getGateway(Requests::class);
        $statusId = $requestGw->getEnumVariant('status', Requests::STATUS_CONFIRM)->id;
        $request->status = $orm->createProxy(EnumElement::className(), $statusId);
        $request->actions->addItem($requestAction);
        try {
            $orm->save($request);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    private function cancelRequest() {
        $orm = $this->ormManager();
        $request = Application::getInstance()->getContext()->getRequest();
        $id = $request->get('id');

        /** @var Requests $request */
        $request = $orm->getById(Requests::class, $id);

        $propertyObjectTypeList = $this->getProperties(IBLOCK_REQUESTS_ID);
        $propertyObjectTypeList = $propertyObjectTypeList['OBJECT_TYPE']['ENUMS'];

        if ($propertyObjectTypeList[$request->objectType->id]['XML_ID'] == 'user') {
            $elementId = $request->user->id;
            $iblockId = IBLOCK_USERS_ID;
        }
        if ($propertyObjectTypeList[$request->objectType->id]['XML_ID'] == 'patient') {
            $elementId = $request->patient->id;
            $iblockId = IBLOCK_PATIENTS_ID;
        }

        if (!isset($elementId) || !isset($iblockId)) {
            return false;
        }

        $requestAction = $this->createRequestActions($request->name, 'reject');

        if (!$requestAction) {
            return false;
        }
        $requestGw = $orm->getGateway(Requests::class);
        $statusId = $requestGw->getEnumVariant('status', Requests::STATUS_REJECTED)->id;
        $request->status = $orm->createProxy(EnumElement::className(), $statusId);
        $request->actions->addItem($requestAction);
        try {
            $orm->save($request);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    private function createRequestActions($name, $action) {
        $orm = $this->ormManager();
        $actionGw = $orm->getGateway(RequestActions::class);
        $actionId = $actionGw->getEnumVariant('action', $action)->id;

        $result = new RequestActions();
        $result->name = $name;
        $result->action = $orm->createProxy(EnumElement::className(), $actionId);
        $result->date = new DateTime();
        $result->author = $orm->createProxy(User::className(), $this->userId);
        try {
            $orm->save($result);
            return $result;
        } catch (Exception $e) {
            return false;
        }
    }

    private function ormManager() {
        return Module::getInstance()->getService('ormManager');
    }

    private function getText() {
        $arText['TITLE'] = '';
        $arText['USER_COL'] = '';
        $arText['EMPTY_TEXT'] = '';

        if ($this->role == self::ROLE_CODE_ADMIN) {
            $arText['EMPTY_TEXT'] = Loc::getMessage("ELEMENT_ADMIN_NONE_TEXT");
            $arText['TITLE'] = Loc::getMessage("TITLE_ADMIN");
            $arText['USER_COL'] = Loc::getMessage("USER_COL_ADMIN");
        }
        if ($this->role == self::ROLE_CODE_MANAGER) {
            $arText['EMPTY_TEXT'] = Loc::getMessage("ELEMENT_MANAGER_NONE_TEXT");
            $arText['TITLE'] = Loc::getMessage("TITLE_MANAGER");
            $arText['USER_COL'] = Loc::getMessage("USER_COL_MANAGER");
        }
        return $arText;
    }

    private function getRoles() {
        $roles = [];
        $rsElement = \CIBlockElement::GetList(
            [],
            ['ACTIVE' => 'Y', 'IBLOCK_ID' => IBLOCK_ROLE_ID],
            false,
            false,
            ['ID', 'NAME']
        );
        while ($arElement = $rsElement->Fetch()) {
            $roles[$arElement['ID']] = $arElement['NAME'];
        }
        return $roles;
    }

    private function getNavLink() {
        $request = Application::getInstance()->getContext()->getRequest();
        foreach ($request->getQueryList() as $code => $value) {
            if (strripos($code,'PAGEN_') !== false) {
                return '/notice/?' . $code . '=' . $value;
            }
        }
        return '#';
    }
}