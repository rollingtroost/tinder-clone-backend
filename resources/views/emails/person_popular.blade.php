<!doctype html>
<html>
<body>
    <h2>Popular Person Alert</h2>
    <p>The following person has received more than 50 likes:</p>
    <ul>
        <li><strong>Name:</strong> {{ $person->name }}</li>
        <li><strong>Age:</strong> {{ $person->age }}</li>
        <li><strong>Bio:</strong> {{ $person->bio }}</li>
        <li><strong>Likes:</strong> {{ $likeCount }}</li>
        <li><strong>Location:</strong> {{ $person->latitude }}, {{ $person->longitude }}</li>
    </ul>
    <p>Pictures:</p>
    <ul>
        @foreach(($person->pictures ?? []) as $url)
            <li>{{ $url }}</li>
        @endforeach
    </ul>
</body>
</html>