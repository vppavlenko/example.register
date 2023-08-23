<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;

/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
?>

<? if(empty($arResult['ITEMS']) && count($arResult['REPORTS_FILTER']) <= 2): ?>

    <div class="page__main-content">
        <div class="page__info-wrapper">
            <div class="info-block">
                <p class="info-block__img-search"></p>
                <h1><?= Loc::getMessage("REPORT_LIST_TITLE"); ?></h1>
                <p class="info-block__text"><?= Loc::getMessage("ELEMENT_NONE_TEXT"); ?></p>
            </div>
        </div>
    </div>

<? else: ?>

    <div class="page__main-content">

        <div class="modal" data-modal-name="downloadReports">
            <button class="modal__close js-modal-close" type="button"></button>
            <div class="modal__in">
                <p class="modal__title">
                    <?=Loc::getMessage("MODAL_TITLE_DOWNLOAD");?>
                </p>
                <p class="modal__text">
                    <?=Loc::getMessage("MODAL_TEXT_DOWNLOAD");?>
                    <span class="countElements"></span>
                </p>
                <div class="modal__buttons">
                    <button class="modal__button button button--min button--white js-modal-close" type="button"><?= Loc::getMessage("CLOSE_DOWNLOAD") ?></button>
                    <a class="modal__button button--min button--attention button download-modal-button"><?= Loc::getMessage("DOWNLOAD") ?></a>
                </div>
            </div>
        </div>

        <div class="modal" data-modal-name="removeReports">
            <button class="modal__close js-modal-close" type="button"></button>
            <div class="modal__in">
                <p class="modal__title"><?=Loc::getMessage("MODAL_TITLE_REMOVE");?></p>
                <p class="modal__text">
                    <?= Loc::getMessage("MODAL_TEXT_REMOVE") ?>
                    <span class="countElements"></span>
                </p>
                <div class="modal__buttons">
                    <button class="modal__button button button--min button--white js-modal-close" type="button">
                        <?= Loc::getMessage("CLOSE") ?>
                    </button>
                    <button class="modal__button button--min button--attention button js-confirm" type="button" href="#">
                        <?= Loc::getMessage("REMOVE") ?>
                    </button>
                </div>
            </div>
        </div>

        <div class="page-info js-upload" data-modal-name="modal-footer">
            <div class="page-info__in">
                <p class="page-info__text"><?=Loc::getMessage("SELECT_REPORTS");?></p>
                <div class="page-info__buttons">
                    <a class="button--transparent page-info__button button js-download-reports" href="/reports/?action=download">
                        <?=Loc::getMessage("BUTTON_DOWNLOAD");?>
                    </a>
                    <button class="button--transparent button--red js-remove-reports page-info__button button" href="#">
                        <?=Loc::getMessage("BUTTON_DELETE");?>
                    </button>
                </div>
            </div>
        </div>

        <div class="page__wrapper">
            <div class="page__rug">
                <div class="section-head">
                    <h1 class="section-head__title page-heading"><?= Loc::getMessage("REPORT_LIST_H1"); ?></h1>
                </div>
            </div>
            <div class="page__rug">
                <table class="persons-list" cellpadding="0" cellspacing="0">
                    <tbody>
                    <tr class="persons-list__tr">
                        <th class="input-cell persons-list__cell"></th>
                        <th class="name persons-list__cell"><?= Loc::getMessage("COL_DOCUMENT_NAME") ?></th>
                        <th class="type persons-list__cell"><?= Loc::getMessage("COL_DOCUMENT_TYPE") ?></th>
                        <th class="format persons-list__cell"><?= Loc::getMessage("COL_DOCUMENT_FORMAT") ?></th>
                        <th class="size persons-list__cell"><?= Loc::getMessage("COL_DOCUMENT_SIZE") ?></th>
                        <th class="date persons-list__cell"><?= Loc::getMessage("COL_DOCUMENT_DATE") ?></th>
                        <th class="download persons-list__cell"></th>
                    </tr>
                    </tbody>
                </table>
            </div>
            <div class="js-page-content">
                <div class="page__filter">
                    <form class="filter" action="<?= $APPLICATION->GetCurPage() ?>" method="POST" id="filter">
                        <div class="filter__item input-cell">
                            <p class="filter-input">
                                <input class="filter-input__checkbox js-check-all-reports" type="checkbox" id="check-all" placeholder="" name="input-cell"/>
                                <label class="filter-input__label" for="check-all"></label>
                            </p>
                        </div>
                        <div class="filter__item name">
                            <p class="filter-input">
                                <input class="filter-input__search js-evt-search" type="text" name="name">
                                <i class="filter-input__icon-search"></i>
                            </p>
                        </div>
                        <div class="filter__item type">
                            <div class="filter-select">
                                <select class="js-filter-select-single" name="type">
                                    <option value=""
                                        <?if(empty($arResult['REPORTS_FILTER']['PROPERTY_REPORT_TYPE'])):?>
                                            selected
                                        <?endif;?>
                                    >
                                        <?=Loc::getMessage("ALL");?>
                                    </option>
                                    <? foreach ($arResult['FILTER']['TYPE'] as $type): ?>
                                        <option
                                            value="<?=$type['ID'];?>"
                                            <?if(!empty($arResult['REPORTS_FILTER']['PROPERTY_REPORT_TYPE'] && ($arResult['REPORTS_FILTER']['PROPERTY_REPORT_TYPE'] == $type['ID']))):?>
                                                selected
                                            <?endif;?>
                                        >
                                            <?=$type['VALUE'];?>
                                        </option>
                                    <? endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="filter__item format">
                            <div class="filter-select">
                                <select class="js-filter-select-single" name="format">
                                    <option value=""
                                        <?if(empty($arResult['REPORTS_FILTER']['PROPERTY_DOCUMENT_TYPE'])):?>
                                            selected
                                        <?endif;?>
                                    >
                                        <?=Loc::getMessage("ALL");?>
                                    </option>
                                    <? foreach ($arResult['FILTER']['FORMAT'] as $format): ?>
                                        <option
                                            value="<?=$format['ID'];?>"
                                            <?if(!empty($arResult['REPORTS_FILTER']['PROPERTY_DOCUMENT_TYPE'] && ($arResult['REPORTS_FILTER']['PROPERTY_DOCUMENT_TYPE'] == $format['ID']))):?>
                                                selected
                                            <?endif;?>
                                        >
                                            <?=$format['VALUE'];?>
                                        </option>
                                    <? endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="filter-delete">
                            <a href="<?= $arParams['SEF_FOLDER'] ?>">
                                <p class="filter-select__in"><?=Loc::getMessage("DELETE_FILTER");?></p>
                            </a>
                        </div>
                    </form>
                </div>

                <div class="page__rug">
                    <input type="hidden" id="pageType" value="reports" />
                    <table class="persons-list" cellpadding="0" cellspacing="0">
                        <tbody>

                        <? foreach ($arResult['ITEMS'] as $arItem): ?>
                            <tr class="js-checked persons-list__tr" data-id="<?=$arItem['ID'];?>">
                                <td class="persons-list__cell input-cell">
                                    <input
                                        class="persons-list__input js-persons-input"
                                        type="checkbox"
                                        <?if(in_array($arItem['ID'], $arResult['CHECKED_ITEMS'])):?>
                                            checked
                                        <?endif;?>
                                        id="<?=$arItem['ID'];?>"
                                        value="<?=$arItem['ID'];?>" />
                                    <label class="persons-list__checkbox" id="<?=$arItem['ID'];?>"></label>
                                </td>
                                <td class="name persons-list__cell">
                                    <?=$arItem['NAME'];?>
                                </td>
                                <td class="type persons-list__cell">
                                    <?=$arItem['PROPERTY_REPORT_TYPE_VALUE'];?>
                                </td>
                                <td class="format persons-list__cell">
                                    <?=$arItem['PROPERTY_DOCUMENT_TYPE_VALUE'];?>
                                </td>
                                <td class="size persons-list__cell">
                                    <?=$arItem['PROPERTY_DOCUMENT_SIZE_VALUE'];?> <?=Loc::getMessage("DOCUMENT_SIZE_MEASURE");?>
                                </td>
                                <td class="date persons-list__cell">
                                    <?=$arItem['DATE_CREATE'];?>
                                </td>
                                <td class="download persons-list__cell">
                                    <div class="persons-list__buttons">
                                        <?if (!empty($arItem['REPORT_FILE']['SRC'])):?>
                                            <a class="persons-list__btn-control download-btn" download href="<?=$arItem['REPORT_FILE']['SRC'];?>"></a>
                                        <?endif;?>
                                    </div>
                                </td>
                            </tr>
                        <? endforeach; ?>

                        <? if (empty($arResult['ITEMS'])): ?>
                            <tr class="persons-list__tr">
                                <td class="persons-list__cell"><?= Loc::getMessage("ELEMENT_NONE") ?></td>
                            </tr>
                        <? endif; ?>

                        </tbody>
                    </table>

                    <?= $arResult['NAV_STRING'] ?>

                </div>
            </div>
        </div>
    </div>

<?endif;  ?>