@extends('master')
@section('title', 'Detail Report Canvasser')
@section('css')
<style>
.chart-card{height:100%}.chart-wrap{position:relative;min-height:300px}.ajax-loading{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;background:#fff;z-index:2}.metric-note{min-height:48px}
</style>
@endsection
@section('content')
<div class="container-fluid">
 <div class="d-flex justify-content-between mb-4"><div><h3 class="mb-1">Detail Report Canvasser</h3><p class="text-muted mb-0">{{ $canvasser->name }} &mdash; {{ $periodLabel }}</p></div><div><span class="badge badge-light p-2">User ID: {{ $canvasser->id }}</span> <span class="badge badge-light p-2">{{ $canvasser->regional ?: 'Regional -' }}</span></div></div>
 <div class="row">
 @foreach([['prospect','Prospect Mix'],['deal','Deal Mix'],['revenue','Revenue Mix'],['target','Target Progress']] as $chart)
  <div class="col-xl-3 col-md-6 mb-4"><div class="card shadow-sm chart-card"><div class="card-header bg-white font-weight-bold">{{ $chart[1] }}</div><div class="card-body"><div class="chart-wrap"><div class="ajax-loading" id="{{ $chart[0] }}Loading"><span><i class="fas fa-spinner fa-spin"></i> Memuat...</span></div><canvas id="{{ $chart[0] }}Chart"></canvas></div><p class="metric-note text-muted small mb-0" id="{{ $chart[0] }}Note"></p></div></div></div>
 @endforeach
 </div>
 <div class="row">
  <div class="col-xl-4 mb-4"><div class="card shadow-sm chart-card"><div class="card-header bg-white font-weight-bold">Perbandingan MoM</div><div class="card-body"><div class="chart-wrap"><div class="ajax-loading" id="momLoading"><span><i class="fas fa-spinner fa-spin"></i> Memuat MoM...</span></div><canvas id="momChart"></canvas></div></div></div></div>
  <div class="col-xl-4 mb-4"><div class="card shadow-sm chart-card"><div class="card-header bg-white font-weight-bold">Tren Top Up {{ $canvasser->name }} — {{ \Carbon\Carbon::createFromFormat('Y-m-d',$month.'-01')->year }}</div><div class="card-body"><div class="chart-wrap"><div class="ajax-loading" id="trendLoading"><span><i class="fas fa-spinner fa-spin"></i> Memuat tren...</span></div><canvas id="trendChart"></canvas></div></div></div></div>
  <div class="col-xl-4 mb-4"><div class="card shadow-sm chart-card"><div class="card-header bg-white font-weight-bold">Tren #User Topup {{ $canvasser->name }} — {{ \Carbon\Carbon::createFromFormat('Y-m-d',$month.'-01')->year }}</div><div class="card-body"><div class="chart-wrap"><div class="ajax-loading" id="userTrendLoading"><span><i class="fas fa-spinner fa-spin"></i> Memuat tren user...</span></div><canvas id="userTrendChart"></canvas></div></div></div></div>
 </div>
 <div class="card shadow-sm mb-4"><div class="card-header bg-white"><h5 class="mb-0">Detail Transaksi — {{ $canvasser->name }} — {{ $periodLabel }}</h5></div><div class="card-body"><div id="transactionLoading" class="text-center py-4"><i class="fas fa-spinner fa-spin"></i> Memuat transaksi...</div><div class="table-responsive"><table id="transactionTable" class="table table-bordered table-striped table-sm w-100"><thead class="thead-dark"><tr><th>Tanggal</th><th>Invoice</th><th>Pelanggan</th><th>Email</th><th>Status Akun</th><th>Nominal</th><th>Payment Method</th><th>Voucher Code</th></tr></thead><tbody></tbody></table></div></div></div>
</div>
@endsection
@section('js')
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded',()=>{
 const params={user_id:@json($canvasser->id),month:@json($month),source:@json($source)}, name=@json($canvasser->name);
 const urls={overview:@json(route('topup-canvasser.detail.overview')),mom:@json(route('topup-canvasser.detail.mom')),trend:@json(route('topup-canvasser.detail.trend')),transactions:@json(route('topup-canvasser.detail.transactions'))};
 const money=v=>new Intl.NumberFormat('id-ID',{style:'currency',currency:'IDR',maximumFractionDigits:0}).format(v||0), num=v=>new Intl.NumberFormat('id-ID').format(v||0), pct=v=>new Intl.NumberFormat('id-ID',{minimumFractionDigits:2,maximumFractionDigits:2}).format(v||0)+'%';
 const request=url=>$.ajax({url,data:params});
 const fail=id=>{$('#'+id).html('<span class="text-danger">Gagal memuat data</span>')};
 const donut=(id,labels,values,title,fmt=num,colors=['#4e73df','#1cc88a'])=>new Chart(document.getElementById(id),{type:'doughnut',data:{labels,datasets:[{data:values,backgroundColor:colors,borderWidth:2}]},options:{maintainAspectRatio:false,cutout:'65%',plugins:{title:{display:true,text:title},tooltip:{callbacks:{label:c=>`${c.label}: ${fmt(c.raw)}`}}}}});

 request(urls.overview).done(d=>{
  ['prospect','deal','revenue','target'].forEach(x=>$('#'+x+'Loading').remove());
  donut('prospectChart',['Leads','Existing Account'],d.prospect,`Komposisi Prospect – ${name}`);
  donut('dealChart',['Deal New Account','Deal Existing Account'],d.deal,`Komposisi Deal Top Up – ${name}`);
  donut('revenueChart',['Top Up New Account','Top Up Existing Account'],d.revenue,`Sumber Pendapatan Top Up – ${name}`,money);
  donut('targetChart',['Target Tercapai','Sisa Target'],d.target.target>0?[d.target.achieved,d.target.remaining]:[0,1],`Progress Target – ${name}`,money,['#1cc88a','#e9ecef']);
  const pt=d.prospect[0]+d.prospect[1]; $('#prospectNote').text(pt?`${name} lebih banyak menangani prospek dari ${d.prospect[0]>d.prospect[1]?'leads baru':'existing account'}.`:'Belum ada data prospect.');
  const rt=d.revenue[0]+d.revenue[1]; $('#revenueNote').text(rt?`${pct(d.revenue[1]/rt*100)} pendapatan berasal dari existing account.`:'Belum ada nominal top up.');
  $('#dealNote').text('Menampilkan komposisi aktivitas deal, bukan conversion rate pelanggan unik.');
  $('#targetNote').html(`<strong>${pct(d.target.achievement)} Achievement</strong>${d.target.over_target>0?`<br><span class="badge badge-success">Over Target ${money(d.target.over_target)}</span>`:''}`);
 }).fail(()=>['prospect','deal','revenue','target'].forEach(x=>fail(x+'Loading')));

 request(urls.mom).done(d=>{$('#momLoading').remove();new Chart(document.getElementById('momChart'),{type:'bar',data:{labels:d.labels,datasets:[{label:'Total Top Up',data:d.values,backgroundColor:['#858796','#4e73df','#f6c23e']}]},options:{maintainAspectRatio:false,plugins:{tooltip:{callbacks:{label:c=>money(c.raw)}}},scales:{y:{beginAtZero:true,ticks:{callback:money}}}}})}).fail(()=>fail('momLoading'));
 request(urls.trend).done(d=>{
  $('#trendLoading').remove();
  $('#userTrendLoading').remove();
  new Chart(document.getElementById('trendChart'),{type:'line',data:{labels:d.rows.map(x=>x.label),datasets:[{label:`Total Top Up ${d.year}`,data:d.rows.map(x=>x.total),borderColor:'#4e73df',backgroundColor:'rgba(78,115,223,.12)',fill:true,tension:.25}]},options:{maintainAspectRatio:false,plugins:{tooltip:{callbacks:{label:c=>money(c.raw)}}},scales:{y:{beginAtZero:true,ticks:{callback:money}}}}});
  new Chart(document.getElementById('userTrendChart'),{type:'line',data:{labels:d.rows.map(x=>x.label),datasets:[{label:`#User Topup ${d.year}`,data:d.rows.map(x=>x.user_count),borderColor:'#1cc88a',backgroundColor:'rgba(28,200,138,.12)',fill:true,tension:.25}]},options:{maintainAspectRatio:false,plugins:{tooltip:{callbacks:{label:c=>`${c.dataset.label}: ${num(c.raw)}`}}},scales:{y:{beginAtZero:true,ticks:{precision:0,callback:num}}}}});
 }).fail(()=>{fail('trendLoading');fail('userTrendLoading')});
 request(urls.transactions).done(r=>{$('#transactionLoading').remove();$('#transactionTable').DataTable({data:r.data,responsive:false,scrollX:true,pageLength:25,order:[[0,'desc']],columns:[
  {data:'tgl_transaksi',render:d=>new Date(d).toLocaleString('id-ID')},{data:'no_invoice',defaultContent:'-'},{data:'company_name',defaultContent:'-'},{data:'masked_email'},{data:'account_status',render:d=>`<span class="badge ${d==='New Account'?'badge-primary':'badge-secondary'}">${d}</span>`},{data:'total_settlement_klien',render:(d,t)=>t==='sort'?Number(d):money(d)},{data:'payment_method_name',defaultContent:'-'},{data:'voucher_code',defaultContent:'-'}]})}).fail(()=>$('#transactionLoading').html('<span class="text-danger">Gagal memuat transaksi</span>'));
});
</script>
@endsection
