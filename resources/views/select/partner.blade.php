@extends('frame.body')
@section('title','Partner School')

@section('section')
    <div class="col-sm-12">
        <form class="form-inline" action="{{URL::current()}}" method="get">
            <div class="form-group">
                <label for="marketer_id">市场专员</label>
                <select class="form-control" name="marketer_id" id="marketer_id">
                    <option value="" @if(is_null($marketer_id)) selected @endif>--全部--</option>
                    @foreach($marketers as $marketer)
                        <option value="{{$marketer->id}}"
                                @if($marketer_id == $marketer->id) selected @endif>{{$marketer->nickname}}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="is_partner">合作校</label>
                <select class="form-control" name="is_partner" id="is_partner">
                    <option value="" @if(is_null($is_partner)) selected @endif>--全部--</option>
                    <option value="0" @if($is_partner == '0') selected @endif>非合作校</option>
                    <option value="1" @if($is_partner == '1') selected @endif>合作校</option>
                </select>
            </div>
            <div class="form-group">
                <label for="school_id">学校ID</label>
                <input class="form-control" type="number" name="school_id" value="{{$school_id}}" id="school_id"/>
            </div>
            <input class="btn btn-primary" type="submit" value="查询">
        </form>

    </div>
    @if(!empty($schools))
        @include('select.partner.schools')
    @endif
    @if(!empty($school))
        @include('select.partner.school')
    @endif
@endsection