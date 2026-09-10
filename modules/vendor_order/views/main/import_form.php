
<?php $form = \yii\bootstrap5\ActiveForm::begin(); ?>
<?= $form->field($model, 'file')->fileInput() ?>
<?= \yii\bootstrap5\Html::submitButton('Отправить'); ?>
<?php $form::end(); ?>
