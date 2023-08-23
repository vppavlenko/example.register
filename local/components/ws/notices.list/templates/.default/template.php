<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;

/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */

//Проверяем доступ к элементам управления
$accessOnlyView = $arResult['USER_ACCESS']->accessOnlyView->id;
?>

<? if(count($arResult['ITEMS']) < 1): ?>

    <div class="page__main-content">
        <div class="page__info-wrapper">
            <div class="info-block">
                <p class="info-block__img-search"></p>
                <h1><?= Loc::getMessage("NOTICES_LIST_TITLE"); ?></h1>
                <p class="info-block__text"><?=$arResult['TEXT']['EMPTY_TEXT'];?></p>
            </div>
        </div>
    </div>

<? else: ?>

    <div class="page__main-content">
        <div class="page__wrapper">
            <div class="page__rug">
                <div class="section-head">
                    <h1 class="section-head__title page-heading"><?=$arResult['TEXT']['TITLE'];?></h1>
                </div>
                <div class="page-info page-info--success" data-modal-name="success-confirmed">
                    <div class="page-info__in">
                        <p class="page-info__text">
                            <?=Loc::getMessage("MODAL_SUCCESS_TITLE");?>
                        </p>
                        <div class="page-info__buttons">
                            <button class="page-info__close" type="button">
                                <?=Loc::getMessage("MODAL_SUCCESS_BUTTON");?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="js-page-content">
                <div class="page__rug">
                    <table class="logs-list" cellpadding="0" cellspacing="0">
                        <tbody>
                            <tr>
                                <th class="user logs-list__cell"><?=$arResult['TEXT']['USER_COL'];?></th>
                                <th class="type logs-list__cell"><?=Loc::getMessage("COL_CHANGE_TYPE");?></th>
                                <th class="old-value logs-list__cell"><?=Loc::getMessage("COL_OLD_VALUE");?></th>
                                <th class="new-value logs-list__cell"><?=Loc::getMessage("COL_NEW_VALUE");?></th>
                                <th class="state logs-list__cell"></th>
                            </tr>

                            <? foreach ($arResult['ITEMS'] as $arItem): ?>

                                <tr class="logs-list__tr" id="<?=$arItem['ID'];?>">
                                    <td class="user logs-list__cell">
                                        <?=$arItem['REQUEST_NAME'];?>
                                    </td>
                                    <td class="type logs-list__cell">
                                        <?=$arItem['CHANGE_FIELD_NAME'];?>
                                    </td>
                                    <td class="old-value logs-list__cell">
                                        <?=$arItem['PROPERTIES']['OLD_VALUE']['VALUE'];?>
                                    </td>
                                    <td class="new-value logs-list__cell">
                                        <?=$arItem['PROPERTIES']['NEW_VALUE']['VALUE'];?>
                                    </td>
                                    <td class="state logs-list__cell">
                                        <div class="logs-list__buttons">
                                            <? if(empty($accessOnlyView)): ?>
                                                <button class="logs-list__btn btn js-refresh-changes" type="button" href="<?=(!empty($arResult['NAV_LINK'])) ? $arResult['NAV_LINK'] : '#';?>" data-action="confirm">Подтвердить</button>
                                                <button class="logs-list__btn btn btn--red js-refresh-changes" type="button" href="<?=(!empty($arResult['NAV_LINK'])) ? $arResult['NAV_LINK'] : '#';?>" data-action="cancel">Отменить</button>
                                            <? endif; ?>
                                        </div>
                                    </td>
                                </tr>

                            <?endforeach;?>
                        </tbody>
                    </table>
                    <?=$arResult['NAV_STRING'];?>
                </div>
            </div>
        </div>
    </div>

<?endif;  ?>