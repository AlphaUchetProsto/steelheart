
<div style="padding: 100px">
    <?php if (Yii::$app->session->hasFlash('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= Yii::$app->session->getFlash('success') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (Yii::$app->session->hasFlash('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= Yii::$app->session->getFlash('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php $form = \yii\bootstrap5\ActiveForm::begin(); ?>
    <?= $form->field($model, 'file')->fileInput()->label('Файл') ?>
    <?= \yii\bootstrap5\Html::submitButton('Отправить', ['class' => 'btn btn-primary']); ?>
    <?php $form::end(); ?>
</div>
