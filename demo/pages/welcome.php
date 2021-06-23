
<h1>Welcome!</h1>

<p>this is a thing</p>

<ul>
    <li><a href="/view/about">About</a></li>
    <li><a href="/view/contact">Contact</a></li>
    <li><a href="/routes">Routes</a></li>
</ul>

<form id="form">
    <label>
        <div>Base</div>
        <input id="form_base" type="number" value="2" />
    </label>
    <label>
        <div>Power</div>
        <input id="form_power" type="number" value="10" />
    </label>
    <label>
        <div>Result</div>
        <input id="form_result" type="text" readonly />
    </label>
    <br/>
    <br/>
    <button type="submit">Go</button>
</form>

<script>
var form = document.getElementById('form');

form.addEventListener('submit', function(event) {
    event.preventDefault();

    var base = document.getElementById('form_base').value;
    var power = document.getElementById('form_power').value;

    fetch('/test/' + base + '/power/' + power)
    .then(function(res) {
        return res.text();
    })
    .then(function(text) {
        var result = document.getElementById('form_result');
        result.value = text;
    })
});
</script>
