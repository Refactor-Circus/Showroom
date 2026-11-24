import { t as e } from "./CatalogActionError-BRUgojP9.js";
import { t } from "./CatalogState-Bkly-MlG.js";
import { A as n, C as r, D as i, O as a, f as o, k as s } from "./catalog-DCoO3A8a.js";
import { t as ee } from "./AttributeFlags-DO53IOaY.js";
import { _ as c, f as te, g as l, u, v as d, y as f } from "./structure-DbqcM-iO.js";
import { AtriumIntegrationScreen as p, AtriumPageHeader as m, AtriumState as ne, navigateTo as h } from "@refactor-circus/atrium";
import { UBadge as re, UButton as ie, UCard as ae, UFormField as oe, UInput as se, USelect as ce, UTable as g, UTextarea as le } from "@refactor-circus/atrium/ui";
import { computed as _, createBlock as ue, createCommentVNode as v, createElementBlock as y, createElementVNode as b, createTextVNode as x, createVNode as S, defineComponent as C, isRef as de, openBlock as w, reactive as T, ref as E, toDisplayString as D, unref as O, watch as fe, withCtx as k, withModifiers as A } from "vue";
import { useI18n as j } from "vue-i18n";
import { useRoute as M } from "vue-router";
// src/pages/attributes/[attribute].vue?vue&type=script&setup=true&lang.ts
var N = {
	class: "space-y-4",
	"data-testid": "showroom-attribute"
}, P = {
	key: 0,
	class: "space-y-5"
}, F = { class: "font-semibold" }, I = { class: "font-semibold" }, L = { class: "space-y-4" }, R = ["data-testid"], z = { class: "tabular-nums" }, B = /* @__PURE__ */ C({
	__name: "[attribute]",
	setup(C) {
		let { t: B, locale: V } = j(), pe = M(), H = a(), U = a(), W = _(() => String(pe.params.attribute)), { client: G, data: K, pending: me, failed: he, status: ge, reload: q } = s(async (e) => r(await e.GET("/attributes/{attribute}", { params: { path: { attribute: W.value } } })).data, W), J = n(async (e) => r(await e.GET("/attribute-groups", { params: { query: { per_page: 100 } } }))), Y = _(() => K.value), _e = _(() => Y.value?.options ?? []), X = T({
			group: "",
			label: "",
			settings: "",
			sort_order: "0"
		}), Z = E({
			is_unique: !1,
			is_localizable: !1,
			is_scopable: !1
		}), Q = T({
			code: "",
			label: "",
			sort_order: "0"
		}), $ = E(null);
		fe(K, () => {
			let e = Y.value;
			e && (X.group = e.group ?? "", X.label = e.labels[V.value] ?? "", X.settings = d(e.settings), X.sort_order = String(e.sort_order), Z.value = {
				is_unique: e.is_unique,
				is_localizable: e.is_localizable,
				is_scopable: e.is_scopable
			});
		}, { immediate: !0 });
		async function ve() {
			let e = Y.value, t = c(X.settings);
			if ($.value = t === null ? B("showroom.structure.attributes.invalid_settings") : null, !G || !e || t === null) return;
			let n = {
				group: X.group || null,
				labels: u(e.labels, V.value, X.label),
				...Z.value,
				settings: t,
				sort_order: f(X.sort_order) ?? 0
			};
			await H.run(async () => r(await G.PATCH("/attributes/{attribute}", {
				params: { path: { attribute: W.value } },
				body: i(n)
			}))) && await q();
		}
		async function ye() {
			G && window.confirm(B("showroom.structure.attributes.confirm_delete")) && await H.run(async () => r(await G.DELETE("/attributes/{attribute}", { params: { path: { attribute: W.value } } }))) && await h("/showroom/attributes");
		}
		async function be() {
			if (!G) return;
			let e = {
				code: Q.code.trim(),
				...l(V.value, Q.label),
				sort_order: f(Q.sort_order) ?? 0
			};
			await U.run(async () => r(await G.POST("/attributes/{attribute}/options", {
				params: { path: { attribute: W.value } },
				body: i(e)
			}))) && (Q.code = "", Q.label = "", Q.sort_order = "0", await q());
		}
		async function xe(e) {
			G && window.confirm(B("showroom.structure.attributes.confirm_delete_option", { option: e })) && await U.run(async () => r(await G.DELETE("/attributes/{attribute}/options/{option}", { params: { path: {
				attribute: W.value,
				option: e
			} } }))) && await q();
		}
		let Se = _(() => [
			{
				label: B("showroom.structure.attributes.no_group"),
				value: null
			},
			...X.group && !J.value.some((e) => e.code === X.group) ? [{
				label: X.group,
				value: X.group
			}] : [],
			...J.value.map((e) => ({
				label: o(e.labels, V.value, e.code),
				value: e.code
			}))
		]), Ce = _(() => [
			{
				accessorKey: "code",
				header: B("showroom.catalog.fields.code")
			},
			{
				id: "label",
				header: B("showroom.catalog.fields.label")
			},
			{
				accessorKey: "sort_order",
				header: B("showroom.catalog.fields.sort_order"),
				meta: { class: {
					th: "text-right",
					td: "text-right"
				} }
			},
			{
				id: "actions",
				header: ""
			}
		]);
		return (n, r) => {
			let i = re, a = ie, s = m, c = ce, l = oe, u = se, d = le, f = ae, h = ne, _ = g, C = p;
			return w(), y("div", N, [S(C, {
				"screen-key": "showroom.attribute",
				record: O(W)
			}, {
				default: k(() => [S(t, {
					ready: !O(me) && O(Y) !== null,
					failed: O(he),
					status: O(ge)
				}, {
					default: k(() => [O(Y) ? (w(), y("div", P, [
						S(s, {
							title: O(o)(O(Y).labels, O(V), O(Y).code),
							description: O(Y).code,
							"data-testid": "showroom-attribute-title"
						}, {
							default: k(() => [S(i, {
								color: "neutral",
								variant: "subtle",
								"data-testid": "showroom-attribute-type"
							}, {
								default: k(() => [x(D(O(Y).type), 1)]),
								_: 1
							}), S(a, {
								color: "error",
								variant: "outline",
								icon: "i-lucide-trash-2",
								loading: O(H).busy.value,
								"data-testid": "showroom-attribute-delete",
								onClick: ye
							}, {
								default: k(() => [x(D(O(B)("showroom.catalog.delete")), 1)]),
								_: 1
							}, 8, ["loading"])]),
							_: 1
						}, 8, ["title", "description"]),
						S(e, { action: O(H) }, null, 8, ["action"]),
						S(f, null, {
							header: k(() => [b("h2", F, D(O(B)("showroom.structure.details")), 1)]),
							default: k(() => [b("form", {
								class: "flex max-w-xl flex-col gap-4",
								"data-testid": "showroom-attribute-form",
								onSubmit: A(ve, ["prevent"])
							}, [
								S(l, {
									label: O(B)("showroom.catalog.fields.group"),
									error: O(H).error("group")
								}, {
									default: k(() => [S(c, {
										modelValue: O(X).group,
										"onUpdate:modelValue": r[0] ||= (e) => O(X).group = e,
										items: O(Se),
										placeholder: O(B)("showroom.structure.attributes.no_group"),
										class: "w-64",
										"data-testid": "showroom-attribute-group"
									}, null, 8, [
										"modelValue",
										"items",
										"placeholder"
									])]),
									_: 1
								}, 8, ["label", "error"]),
								S(l, {
									label: O(B)("showroom.catalog.fields.label_in", { locale: O(V) }),
									error: O(H).error("labels")
								}, {
									default: k(() => [S(u, {
										modelValue: O(X).label,
										"onUpdate:modelValue": r[1] ||= (e) => O(X).label = e,
										class: "w-64",
										"data-testid": "showroom-attribute-label"
									}, null, 8, ["modelValue"])]),
									_: 1
								}, 8, ["label", "error"]),
								S(ee, {
									modelValue: O(Z),
									"onUpdate:modelValue": r[2] ||= (e) => de(Z) ? Z.value = e : null,
									testid: "showroom-attribute-flag"
								}, null, 8, ["modelValue"]),
								S(l, {
									label: O(B)("showroom.structure.attributes.settings"),
									hint: O(B)("showroom.structure.attributes.settings_hint"),
									error: O($) ?? O(H).error("settings")
								}, {
									default: k(() => [S(d, {
										modelValue: O(X).settings,
										"onUpdate:modelValue": r[3] ||= (e) => O(X).settings = e,
										rows: 4,
										class: "w-full font-mono",
										"data-testid": "showroom-attribute-settings"
									}, null, 8, ["modelValue"])]),
									_: 1
								}, 8, [
									"label",
									"hint",
									"error"
								]),
								S(l, {
									label: O(B)("showroom.catalog.fields.sort_order"),
									error: O(H).error("sort_order")
								}, {
									default: k(() => [S(u, {
										modelValue: O(X).sort_order,
										"onUpdate:modelValue": r[4] ||= (e) => O(X).sort_order = e,
										type: "number",
										min: "0",
										class: "w-32",
										"data-testid": "showroom-attribute-sort"
									}, null, 8, ["modelValue"])]),
									_: 1
								}, 8, ["label", "error"]),
								b("div", null, [S(a, {
									type: "submit",
									icon: "i-lucide-check",
									loading: O(H).busy.value,
									"data-testid": "showroom-attribute-save"
								}, {
									default: k(() => [x(D(O(B)("showroom.catalog.save")), 1)]),
									_: 1
								}, 8, ["loading"])])
							], 32)]),
							_: 1
						}),
						O(te)(O(Y).type) ? (w(), ue(f, {
							key: 0,
							"data-testid": "showroom-attribute-options"
						}, {
							header: k(() => [b("h2", I, D(O(B)("showroom.structure.attributes.options")), 1)]),
							default: k(() => [b("div", L, [
								S(_, {
									data: O(_e),
									columns: O(Ce)
								}, {
									empty: k(() => [S(h, {
										compact: "",
										title: O(B)("showroom.structure.attributes.no_options")
									}, null, 8, ["title"])]),
									"code-cell": k(({ row: { original: e } }) => [b("span", {
										class: "font-mono text-xs",
										"data-testid": `showroom-option-row-${e.code}`
									}, D(e.code), 9, R)]),
									"label-cell": k(({ row: { original: e } }) => [x(D(O(o)(e.labels, O(V), e.code)), 1)]),
									"sort_order-cell": k(({ row: { original: e } }) => [b("span", z, D(e.sort_order), 1)]),
									"actions-cell": k(({ row: { original: e } }) => [S(a, {
										variant: "ghost",
										size: "sm",
										icon: "i-lucide-trash-2",
										"aria-label": O(B)("showroom.catalog.delete"),
										"data-testid": `showroom-option-delete-${e.code}`,
										onClick: (t) => xe(e.code)
									}, null, 8, [
										"aria-label",
										"data-testid",
										"onClick"
									])]),
									_: 1
								}, 8, ["data", "columns"]),
								S(e, { action: O(U) }, null, 8, ["action"]),
								b("form", {
									class: "flex flex-wrap items-end gap-3",
									"data-testid": "showroom-option-create-form",
									onSubmit: A(be, ["prevent"])
								}, [
									S(l, {
										label: O(B)("showroom.catalog.fields.code"),
										error: O(U).error("code"),
										required: ""
									}, {
										default: k(() => [S(u, {
											modelValue: O(Q).code,
											"onUpdate:modelValue": r[5] ||= (e) => O(Q).code = e,
											required: "",
											class: "w-48",
											"data-testid": "showroom-option-create-code"
										}, null, 8, ["modelValue"])]),
										_: 1
									}, 8, ["label", "error"]),
									S(l, {
										label: O(B)("showroom.catalog.fields.label_in", { locale: O(V) }),
										error: O(U).error("labels")
									}, {
										default: k(() => [S(u, {
											modelValue: O(Q).label,
											"onUpdate:modelValue": r[6] ||= (e) => O(Q).label = e,
											class: "w-56",
											"data-testid": "showroom-option-create-label"
										}, null, 8, ["modelValue"])]),
										_: 1
									}, 8, ["label", "error"]),
									S(l, {
										label: O(B)("showroom.catalog.fields.sort_order"),
										error: O(U).error("sort_order")
									}, {
										default: k(() => [S(u, {
											modelValue: O(Q).sort_order,
											"onUpdate:modelValue": r[7] ||= (e) => O(Q).sort_order = e,
											type: "number",
											min: "0",
											class: "w-28",
											"data-testid": "showroom-option-create-sort"
										}, null, 8, ["modelValue"])]),
										_: 1
									}, 8, ["label", "error"]),
									S(a, {
										type: "submit",
										icon: "i-lucide-plus",
										loading: O(U).busy.value,
										"data-testid": "showroom-option-create"
									}, {
										default: k(() => [x(D(O(B)("showroom.structure.attributes.add_option")), 1)]),
										_: 1
									}, 8, ["loading"])
								], 32)
							])]),
							_: 1
						})) : v("", !0)
					])) : v("", !0)]),
					_: 1
				}, 8, [
					"ready",
					"failed",
					"status"
				])]),
				_: 1
			}, 8, ["record"])]);
		};
	}
});
export { B as default };
