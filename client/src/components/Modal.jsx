export default function Modal({ title, onClose, children, wide }) {
  return (
    <div className="modal-overlay" onClick={onClose}>
      <div className={`modal-box${wide ? " wide" : ""}`} onClick={(e) => e.stopPropagation()}>
        <h5>{title}</h5>
        {children}
      </div>
    </div>
  );
}