<div class="modal fade" id="baseModal" tabindex="-1" role="dialog" aria-labelledby="modalLabel">
    <div class="modal-dialog @yield('modal_size')" role="document">
        <div class="modal-content border-radius-5">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <i class="fa fa-close"></i></button>
                <h4 class="modal-title" id="modalLabel">@yield('modal_title')</h4>
            </div>
            <div class="modal-body">
                @yield('modal_body')
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                @yield('modal_submit')
            </div>
        </div>
    </div>
</div>