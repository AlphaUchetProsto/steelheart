<div class="white-rectangle">
    <button class="add-product bitrix-primary">
        Добавить товар
    </button>
</div>

<input id="dealId" type="hidden" value="<?=$dealId?>">

<select class="form-select currency-template" aria-label="Default select example">
    <?php foreach ($currency as $key => $item) : ?>
        <option value="<?=$key?>"><?=$item?></option>
    <?php endforeach; ?>
</select>

<div class="white-rectangle">
    <table class="table">
        <thead>
        <tr>
            <td></td>
            <td></td>
            <td>Part Number</td>
            <td>Товар</td>
            <td>Срок доставки</td>
            <td>Цена</td>
            <td>Количество</td>
            <td>Сумма</td>
            <td>Валюта</td>
        </tr>
        </thead>
        <tbody class="align-middle">
        <?php foreach ($products as $key => $product) : ?>
        <tr id="<?=$key + 1?>">
            <td>
                <div class="open-menu" onclick="triggerMenu(this)" type="button">
                    <span></span>
                    <span></span>
                    <span></span>
                    <div class="menu shadow">
                        <button onclick="copyRow(this)">
                            Копировать
                        </button>
                        <button onclick="deleteRow(this)">
                            Удалить
                        </button>
                    </div>
                </div>
            </td>
            <td class="number">
                <?=$key + 1?>
            </td>
            <td>
                <label>
                    <input type="text" class="form-control partNumber" value="<?=$product['partNumber']?>">
                </label>
            </td>
            <td>
                <label>
                    <input type="text" class="form-control product" oninput="checkEmptyProducts(this)" value="<?=$product['name']?>">
                </label>
            </td>
            <td>
                <label>
                    <input type="text" class="form-control delivery-time" value="<?=$product['delivery-time']?>">
                </label>
            </td>
            <td>
                <label>
                    <input type="number" class="form-control price" oninput="recountSum(this)" value="<?=$product['price']?>">
                </label>
            </td>
            <td>
                <label>
                    <input type="number" class="form-control count" oninput="recountSum(this)" value="<?=$product['count']?>">
                </label>
            </td>
            <td>
                <label>
                    <input disabled readonly type="number" class="form-control sum" oninput="recountSum(this)" value="<?=$product['sum']?>">
                </label>
            </td>
            <td>
                <select class="form-select currency" aria-label="Default select example" onchange="recountTable()">
                    <?php foreach ($currency as $key => $item) : ?>
                        <option <?=$key == $product['currency'] ? 'selected' : ''?> value="<?=$key?>"><?=$item?></option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="white-rectangle bottom-element">
    <div class="result-list">
    </div>
</div>

<div class="bottom-menu">
    <button disabled class="save bitrix-success disabled">
        Сохранить
    </button>
</div>