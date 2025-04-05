<div class="flex items-center justify-between mt-4 text-center">
    <div>
        <h1>Monthly Target </h1>
        <div class="text-lg">{{ $data['target'] }}</div>
    </div>

    <div>
        <h1>Monthly Collection</h1>
        <div class="text-lg text-sky-400">{{ $data['actual'] }}</div>
    </div>
    <div>
        <h1>Percentage</h1>
        <div class="text-lg">{{ $data['percent'] }}</div>
    </div>
</div>
