function pai_record(pai_id) {
    location.href = ROOT + '?m=PaiGoods&a=pai_record&id=' + pai_id;
}

function price_record(pai_id) {
    location.href = ROOT + '?m=PaiGoods&a=price_record&id=' + pai_id;
}

function security_deposit_records(pai_id) {
    location.href = ROOT + '?m=PaiGoods&a=security_deposit_records&id=' + pai_id;
}

function detail(id) {
    location.href = ROOT + '?m=PaiGoods&a=detail&id=' + id;
}