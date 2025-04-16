(function() { "use strict";

/**
 * Data storage for UNDO / REDO states as well as currently ongoing operations.
 */
class UndoRedoQueue
{
    // Class checks for state of its operations once every this many ms
    const MAIN_LOOP_DELAY = 100;

    // operation is considered failed if it takes longer than this timeout
    const OP_TIMEOUT = 7000;

    // Operations in state == 'done'
    undo_states;

    // Operations in state == 'undone'
    redo_states;

    // Operations in states: 'waiting', 'running', 'before_undoing', 'undoing', 'before_redoing', 'redoing'
    ongoing_operations;

    current_operation_promise;
    _noop;

    constructor() {
        this.undo_states = [];
        this.redo_states = [];
        this.ongoing_operations = [];
        this._noop = new Promise(resolve => resolve());
        this.current_operation_promise = this._noop;

        this.mainLoop();
    }

    async mainLoop() {
        var op;
        const publication_controller = $("#js-wa-header-publish").data('controller');
        while (true) {
            if (!this.ongoing_operations.length) {
                await new Promise(resolve => setTimeout(resolve, this.MAIN_LOOP_DELAY));
                continue;
            }

            op = this.ongoing_operations[0];
            if (op.delay > 0) {
                var wait_time = Math.min(op.delay, this.MAIN_LOOP_DELAY);
                for (op of this.ongoing_operations) {
                    if (op.delay > 0) {
                        op.delay -= wait_time;
                        if (op.delay < 0) {
                            op.delay = 0;
                        }
                    }
                }

                await new Promise(resolve => setTimeout(resolve, this.MAIN_LOOP_DELAY));
                continue;
            }

            if (op.state == 'waiting') {
                this.redo_states = [];
            }

            publication_controller.spinnerOn();
            try {
                this.current_operation_promise = Promise.race([
                    this._runOngoingOperation(op),
                    new Promise(resolve => setTimeout(resolve, this.OP_TIMEOUT))
                ]);
                await this.current_operation_promise;
            } catch (e) {
                console.log('ERROR during save operation', e);
            }
            this.current_operation_promise = this._noop;
            publication_controller.spinnerOff();
            if (op.state == 'done') {
                this.undo_states.push(op);
            } else if (op.state == 'undone') {
                this.redo_states.push(op);
                if (this.undo_states.length <= 0) {
                    publication_controller.undoneFully();
                }
            } else {
                console.log('WARNING: operation in wrong state after having been performed', op.state);
            }
            if (op !== this.ongoing_operations.shift()) {
                console.log('WARNING: head of ongoing_operations modified during execution');
            }

            // Make sure all _callSynced() operations take effect
            await new Promise(resolve => setTimeout(resolve, 0));
        }
    }

    _runOngoingOperation(op) {
        if (op.state == 'waiting') {
            return op.run();
        } else if (op.state == 'before_undoing') {
            return op.undo();
        } else if (op.state == 'before_redoing') {
            return op.redo();
        } else {
            console.log('WARNING: Incompatible operation state in ongoing_operations:', op.state);
            return this._noop;
        }
    }

    // Called when user invokes an undo operation (e.g. via a button in wa_header)
    undo() {
        if (!this.undo_states.length) {
            return false;
        }

        this._callSynced(() => {
            // cancel this.ongoing_operations if contains 'waiting' or 'before_redoing' state, см. txt
            /* !!!
            for (var i = this.ongoing_operations.length - 1; i >= 0; i--) {
                var op = this.ongoing_operations[i];
                if (i.state == 'waiting' || i.state == 'undone') {

                }
            }
            */

            var op = this.undo_states.pop();
            op.prepareUndo();
            this.ongoing_operations.push(op);
        });
        return true;
    }

    // Called when user invokes a redo operation (e.g. via a button in wa_header)
    redo() {
        if (!this.redo_states.length) {
            return false;
        }
        this._callSynced(() => {
            // !!! TODO: cancel this.ongoing_operations if contans 'before_undoing' state, см. txt
            var op = this.redo_states.pop();
            op.prepareRedo();
            this.ongoing_operations.push(op);
        });
        return true;
    }

    _callSynced(fn) {
        var that = this;
        if (this.current_operation_promise.finally) {
            this.current_operation_promise.finally(cb);
        } else {
            this.current_operation_promise.always(cb);
        }

        function cb() {
            fn.call(that);
        }
    }

    addOperation(block_id, op) {
        op.block_id = block_id;

        // 'merge', 'replace' or 'add'
        const mode = op.mode || 'merge';

        if (mode !== 'add') {
            // Remove all operations of the same block_id and type, possibly merging data into new `op`
            this.ongoing_operations = this.ongoing_operations.filter(function(old_op) {
                if (old_op.state === 'waiting' && old_op.block_id === block_id && old_op.type === op.type) {
                    if (mode === 'merge') {
                        op.merge(old_op);
                    }
                    return false;
                }
                return true;
            });
        }

        this.ongoing_operations.push(op);
        return op;
    }

}

class Operation
{
    // States:
    // waiting -> running -> done <-> before_undoing -> undoing -> undone <-> before_redoing -> redoing -> done
    //        `-> undone
    // waiting:
    // - may `run()` -> 'running'
    // //- OR may `cancel()` -> undone.
    // - OR may `another_operation.merge(this_operation)`
    //   (if another_operation has the same `.type` and `.block_id`)
    //   and then it's safe to replace with `another_operation`
    // running: no action allowed; will switch to 'done' after a while.
    // done: may `prepareUndo()` -> 'before_undoing'
    // before_undoing:
    // - may `undo()` -> 'undoing'
    // //- OR may `cancelUndo()` -> 'done'
    // undoing: no action allowed; will switch to 'undone' after a while.
    // undone: may `prepareRedo()` -> 'before_redoing'
    // before_redoing:
    // - may `redo()` -> 'redoing'
    // //- OR may `cancelRedo()` -> 'undone'
    // redoing: no action allowed; will switch to 'done' after a while.
    state;

    type;
    mode; // replace|add|merge
    delay;
    block_id;
    op;

    _merge;
    _undo;
    _run;

    constructor(op) {
        this.delay = op.delay || 0;
        this.state = 'waiting';
        this.block_id = op.block_id;
        this.mode = op.mode || 'add';
        this.type = op.type;
        this.op = op;

        this._undo = op.undo;
        this._run = op.run;
        this._redo = op.redo || op.run;
        this._localUndo = op.localUndo;
        this._localRedo = op.localRedo;
        this._merge = op.merge || function(op, old_op) {};
    }

    merge(old_operation) {
        if (this.state !== 'waiting') {
            throw new Error('Incompatible operation state for merge:', this.state);
        } else if (this.block_id !== old_operation.block_id) {
            throw new Error('Incompatible block_id for merge:', this.block_id, old_operation.block_id);
        } else if (this.type !== old_operation.type) {
            throw new Error('Incompatible operation type for merge:', this.type, old_operation.type);
        }
        this._merge(this.op, old_operation.op);
    }

    _ensureState(states) {
        if (Array.isArray(states)) {
            if (states.indexOf(this.state) >= 0) {
                return;
            }
        }
        if (this.state === states) {
            return;
        }

        throw new Error('Incompatible state:', this.state, states);
    }

    async run() {
        this._ensureState('waiting');
        this.state = 'running';
        await this._run(this.op);
        this.state = 'done';
    }

    cancel() {
        this._ensureState('waiting');
        this._localUndo(this.op);
        this.state = 'undone';
    }

    prepareUndo() {
        this._ensureState('done');
        this._localUndo(this.op);
        this.state = 'before_undoing';
    }

    async undo() {
        this._ensureState('before_undoing');
        this.state = 'undoing';
        await this._undo(this.op);
        this.state = 'undone';
    }

    prepareRedo() {
        this._ensureState('undone');
        this._localRedo(this.op);
        this.state = 'before_redoing';
    }

    async redo() {
        this._ensureState('before_redoing');
        this.state = 'redoing';
        await this._redo(this.op);
        this.state = 'done';
    }
}

UndoRedoQueue.Operation = Operation;
window.UndoRedoQueue = UndoRedoQueue;

}());