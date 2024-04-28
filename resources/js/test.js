window.addEventListener('load', function () {

    let $list = $('.messages'),
        $messages = $list.find('li'),
        $requestData = {
            id: $messages.last().data('id')
        };

    function getMessages($requestData) {
        fetch('/getMessages', {
            method: 'POST',
            body: JSON.stringify($requestData),
            headers: {
                'Content-type': 'application/json; charset=UTF-8',
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
        })
            .then((response) => response.json())
            .then((data) => {

                if (data.status === true) {
                    data.data.forEach((message) => {
                        $list.append(`<li data-id="${message.id}">` + message.text + `</li>`);
                    })

                    $requestData.id = data.data[Object.keys(data.data).pop()].id;
                }

                getMessages($requestData);
            })

    }

    getMessages($requestData);
})
